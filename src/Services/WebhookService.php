<?php
namespace App\Services;

use App\Entity\PurchaseToken;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class WebhookService
 */
class WebhookService
{
    const TIMEOUT = 5.0;

    /**
     * @param PurchaseToken $purchaseToken
     * @param bool          $success
     *
     * @return string
     * @throws GuzzleException
     */
    public function send(PurchaseToken $purchaseToken, $success)
    {
        $body = [
            'success'       => $success,
            'token'         => $purchaseToken->getToken(),
            'code'          => $purchaseToken->getId(),
            'price'         => $purchaseToken->getPrice(),
            'transactionID' => $purchaseToken->getTransactionID()
        ];
        $headers = [
            'Accept'          => 'application/json',
            'Content-Type'    => 'application/json'
        ];
        $options = [
            'headers' => $headers,
            'body'    => json_encode($body)
        ];

        $client = new Guzzle([
            'timeout' => self::TIMEOUT
        ]);
        $resp = $client->request('POST', $purchaseToken->getWebhookURL(), $options);

        return (string)$resp->getBody();
    }
}
