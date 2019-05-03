<?php
namespace App\Services;

use Stripe\ApiResource;
use Stripe\Checkout\Session;
use Stripe\Error\Api;
use Stripe\Event;
use Stripe\Stripe;

/**
 * Class StripeService
 */
class StripeService
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * Constructor
     *
     * @param string $apiKey
     * @param string $secretKey
     */
    public function __construct($apiKey, $secretKey)
    {
        $this->apiKey    = $apiKey;
        $this->secretKey = $secretKey;
        Stripe::setApiKey($secretKey);
    }

    /**
     * @param array $item
     * @param string $token
     * @param string $successURL
     * @param string $cancelURL
     *
     * @return ApiResource
     */
    public function createSession(array $item, $token, $successURL, $cancelURL)
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'client_reference_id'  => $token,
            'line_items' => [$item],
            'success_url' => $successURL,
            'cancel_url'  => $cancelURL
        ]);
    }

    /**
     * @param string $token
     * @param int    $gte
     *
     * @return Session|null
     * @throws Api
     */
    public function findByToken($token, $gte = null)
    {
        $events = Event::all([
            'type' => 'checkout.session.completed',
            'created' => [
                'gte' => ($gte ?? time() - 24 * 60 * 60)
            ]
        ]);

        $session = null;
        foreach ($events->autoPagingIterator() as $event) {
            if ($event->data->object['client_reference_id'] === $token) {
                $session = $event->data->object;
                break;
            }
        }

        return $session;
    }
}
