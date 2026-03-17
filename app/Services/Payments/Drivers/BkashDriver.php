<?php

namespace App\Services\Payments\Drivers;

use App\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGatewayInterface;

class BkashDriver implements PaymentGatewayInterface
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
            'checkout_url' => 'https://checkout.pay.bka.sh/mock',
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
            ['name' => 'app_key', 'label' => 'bKash App Key', 'type' => 'text', 'required' => true],
            ['name' => 'app_secret', 'label' => 'bKash App Secret', 'type' => 'password', 'required' => true],
        ];
    }

    public function supportsRefunds(): bool
    {
        return true; // bKash supports refunds via API
    }

    public function getStandardizedResponse(mixed $gatewayResponse): array
    {
        return [
            'success' => $gatewayResponse['success'] ?? false,
            'transaction_id' => $gatewayResponse['trxID'] ?? null,
            'amount' => $gatewayResponse['amount'] ?? 0.0,
            'currency' => $gatewayResponse['currency'] ?? 'BDT',
            'status' => $gatewayResponse['transactionStatus'] ?? 'pending',
            'error_message' => $gatewayResponse['errorMessage'] ?? null,
            'raw_response' => $gatewayResponse,
        ];
    }
}
