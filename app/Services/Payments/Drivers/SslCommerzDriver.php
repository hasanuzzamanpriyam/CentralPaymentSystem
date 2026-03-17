<?php

namespace App\Services\Payments\Drivers;

use App\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGatewayInterface;

class SslCommerzDriver implements PaymentGatewayInterface
{
    protected ?array $credentials;

    public function __construct(?array $credentials = null)
    {
        $this->credentials = $credentials;
    }

    public function initializePayment(Transaction $transaction): array
    {
        // Dummy implementation for now
        return [
            'success' => true,
            'checkout_url' => 'https://sandbox.sslcommerz.com/mock',
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

    public function getRequiredCredentials(): array
    {
        return [
            ['name' => 'store_id', 'label' => 'Store ID', 'type' => 'text', 'required' => true],
            ['name' => 'store_password', 'label' => 'Store Password', 'type' => 'password', 'required' => true],
        ];
    }

    public function supportsRefunds(): bool
    {
        return true; 
    }

    public function getStandardizedResponse(mixed $gatewayResponse): array
    {
        return [
            'success' => $gatewayResponse['success'] ?? false,
            'transaction_id' => $gatewayResponse['val_id'] ?? null,
            'amount' => $gatewayResponse['amount'] ?? 0.0,
            'currency' => $gatewayResponse['currency_type'] ?? 'BDT',
            'status' => $gatewayResponse['status'] ?? 'pending',
            'error_message' => $gatewayResponse['error'] ?? null,
            'raw_response' => $gatewayResponse,
        ];
    }
}
