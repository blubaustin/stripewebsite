<?php
namespace App\Controller;

use App\Entity\PurchaseToken;
use App\Http\Request;
use App\Services\StripeService;
use App\Services\WebhookService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PurchaseController
 */
class PurchaseController extends Controller
{
    /**
     * @Route("/purchase/checkout/stripe/{token}", name="purchase_checkout_stripe")
     *
     * @param string        $token
     * @param Request       $request
     * @param StripeService $stripeService
     *
     * @return Response
     * @throws Exception
     */
    public function checkoutAction($token, Request $request, StripeService $stripeService)
    {
        $request->getSession()->set('token', $token);
        $purchaseToken = $this->getPurchaseTokenOrThrow($token);
        $session       = $stripeService->createSession(
            [
                'name'        => 'Premium Server Upgrade',
                'description' => $purchaseToken->getDescription(),
                'amount'      => $purchaseToken->getPrice(),
                'currency'    => 'usd',
                'quantity'    => 1
            ],
            $token,
            $this->generateUrl('purchase_complete', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->generateUrl('purchase_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return $this->render('purchase/checkout.html.twig', [
            'stripeAPIKey'    => $this->getParameter('stripeApiKey'),
            'stripeSessionID' => $session['id']
        ]);
    }

    /**
     * @Route("/purchase/complete", name="purchase_complete")
     *
     * @param Request        $request
     * @param WebhookService $webhookService
     * @param StripeService  $stripeService
     *
     * @return RedirectResponse
     * @throws Exception
     * @throws GuzzleException
     */
    public function completeAction(Request $request, WebhookService $webhookService, StripeService $stripeService)
    {
        $session = $request->getSession();
        $token   = $session->get('token');
        $session->remove('token');
        if (!$token) {
            $this->logger->error('No token found in session in complete action.');
            throw $this->createNotFoundException();
        }

        $purchaseToken = $this->getPurchaseTokenOrThrow($token);
        if (!$purchaseToken) {
            throw $this->createNotFoundException();
        }

        $session = $stripeService->findByToken($token);
        if (!$session) {
            $this->logger->error('Stripe session not found in complete action.');
            throw $this->createNotFoundException();
        }

        $purchaseToken
            ->setIsSuccess(true)
            ->setIsPurchased(true)
            ->setStripeID($session['id'])
            ->setStripCustomer($session['customer'])
            ->setStripePaymentIntent($session['payment_intent']);
        $this->em->flush();

        try {
            $webhookService->send($purchaseToken, true);
        } catch (Exception $e) {
            // A cron job will keep trying this transaction.
            $purchaseToken->setIsClientFailure(true);
            $this->em->flush();

            return $this->render('purchase/failure.html.twig');
        }

        $url = $purchaseToken->getSuccessURL();
        if (stripos($url, '?') !== false) {
            $url .= '&t=' . $purchaseToken->getToken();
        } else {
            $url .= '?t=' . $purchaseToken->getToken();
        }

        return new RedirectResponse($url);
    }

    /**
     * @Route("/purchase/cancel", name="purchase_cancel")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function cancelAction(Request $request)
    {
        $session = $request->getSession();
        $token   = $session->get('token');
        $session->remove('token');
        if (!$token) {
            $this->logger->error('No token found in session in cancel action.');
            throw $this->createNotFoundException();
        }

        $purchaseToken = $this->getPurchaseTokenOrThrow($token);
        if (!$purchaseToken) {
            throw $this->createNotFoundException();
        }

        return new RedirectResponse($purchaseToken->getCancelURL());
    }

    /**
     * @Route("/purchase/{token}", name="purchase", methods={"GET"})
     *
     * @param string $token
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction($token)
    {
        $purchaseToken = $this->getPurchaseTokenOrThrow($token);

        return $this->render('purchase/index.html.twig', [
            'token'  => $purchaseToken,
            'action' => $this->generateUrl('purchase_checkout_stripe', ['token' => $token])
        ]);
    }

    /**
     * @param string $token
     *
     * @return PurchaseToken|object
     * @throws Exception
     */
    private function getPurchaseTokenOrThrow($token)
    {
        $purchaseToken = $this->em->getRepository(PurchaseToken::class)->findByToken($token);
        if (!$purchaseToken || $purchaseToken->isPurchased()) {
            throw $this->createNotFoundException();
        }

        return $purchaseToken;
    }
}
