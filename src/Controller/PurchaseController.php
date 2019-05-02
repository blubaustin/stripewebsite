<?php
namespace App\Controller;

use App\Entity\PurchaseToken;
use App\Http\Request;
use App\Services\WebhookService;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PurchaseController
 */
class PurchaseController extends AbstractController
{
    const TOKEN_EXPIRATION = 43200; // 12 hours

    /**
     * @Route("/purchase/{token}", name="purchase", methods={"GET"})
     *
     * @param string  $token
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction($token, Request $request)
    {
        $purchaseToken = $this->getPurchaseTokenOrThrow($token);

        return $this->render('purchase/index.html.twig', [
            'token'  => $purchaseToken,
            'action' => $this->generateUrl('purchase_complete', ['token' => $token])
        ]);
    }

    /**
     * @Route("/purchase/complete/{token}", name="purchase_complete")
     *
     * @param string         $token
     * @param WebhookService $webhookService
     *
     * @return RedirectResponse
     * @throws Exception
     * @throws GuzzleException
     */
    public function completeAction($token, WebhookService $webhookService)
    {
        $purchaseToken = $this->getPurchaseTokenOrThrow($token);
        if (!$purchaseToken) {
            throw $this->createNotFoundException();
        }

        $success = true;

        $purchaseToken
            ->setIsSuccess($success)
            ->setIsPurchased(true);
        $this->getDoctrine()->getManager()->flush();

        try {
            $webhookService->send($purchaseToken, $success);
        } catch (Exception $e) {
            // A cron job will keep trying this transaction.
            $purchaseToken
                ->setIsSuccess($success)
                ->setIsPurchased(true)
                ->setIsClientFailure(true);
            $this->getDoctrine()->getManager()->flush();

            return $this->render('purchase/failure.html.twig');
        }

        $url = $purchaseToken->getSuccessURL();
        if (stripos($url, '?') !== false) {
            $url .= '&t=' . $purchaseToken->getTransactionID();
        } else {
            $url .= '?t=' . $purchaseToken->getTransactionID();
        }

        return new RedirectResponse($url);
    }

    /**
     * @param string $token
     *
     * @return PurchaseToken|object
     * @throws Exception
     */
    private function getPurchaseTokenOrThrow($token)
    {
        $repo = $this->getDoctrine()->getRepository(PurchaseToken::class);
        $purchaseToken = $repo->findByToken($token);
        if (!$purchaseToken || $purchaseToken->isPurchased()) {
            throw $this->createNotFoundException();
        }

        $now  = new DateTime();
        $diff = $now->getTimestamp() - $purchaseToken->getDateCreated()->getTimestamp();
        if ($diff > self::TOKEN_EXPIRATION) {
            throw $this->createNotFoundException();
        }

        return $purchaseToken;
    }
}
