<?php
namespace App\Command;

use App\Entity\PurchaseToken;
use App\Services\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessClientFailuresCommand
 */
class ProcessClientFailuresCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:payments:process-client-failures';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var WebhookService
     */
    protected $webhookService;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $em
     * @param WebhookService         $webhookService
     */
    public function __construct(EntityManagerInterface $em, WebhookService $webhookService)
    {
        parent::__construct();

        $this->em             = $em;
        $this->webhookService = $webhookService;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws Exception
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paymentTokens = $this->em->getRepository(PurchaseToken::class)->findByClientFailure();
        foreach($paymentTokens as $purchaseToken) {
            $this->process($purchaseToken, $output);
        }
    }

    /**
     * @param PurchaseToken   $purchaseToken
     * @param OutputInterface $output
     *
     * @throws GuzzleException
     */
    protected function process(PurchaseToken $purchaseToken, OutputInterface $output)
    {
        $output->writeln('Processing ' . $purchaseToken->getToken());

        try {
            $this->webhookService->send($purchaseToken, true);
            $purchaseToken->setIsClientFailure(false);
            $this->em->flush();
        } catch (Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
        }
    }
}
