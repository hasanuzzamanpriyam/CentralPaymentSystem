<?php

namespace App\Services\Payments\Drivers;

use App\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGatewayInterface;

class StripeDriver implements PaymentGatewayInterface
{
    protected ?array $credentials;

    public function __construct(?array $credentials = null)
    {
        $this->credentials = $credentials;
        // e.g., \Stripe\Stripe::setApiKey($this->credentials['secret_key'] ?? config('services.stripe.secret'));
    }

    public function initializePayment(Transaction $transaction): array
    {
        // Dummy implementation for now
        return [
            'success' => true,
            'checkout_url' => 'https://checkout.stripe.com/mock',
        ];
    }

    public function verifyPayment(string $gatewayTxId): array
    {
        return [
            'success' => true,
            'status' => 'completed',
        ];
    }

    public function refund(Transaction $transaction): array
    {
        return [
            'success' => true,
            'status' => 'refunded',
        ];
    }
}
