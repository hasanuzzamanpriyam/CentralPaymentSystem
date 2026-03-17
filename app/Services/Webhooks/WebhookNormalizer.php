<?php

namespace App\Services\Webhooks;

class WebhookNormalizer
{
    /**
     * Normalize incoming webhooks from varying payment gateways 
     * into a consistent JSON payload for the external merchant applications.
     */
    public function normalize(array $payload, string $gateway): array
    {
        // Acts as a mapping engine for outgoing webhooks sent to MERN/WP
        switch ($gateway) {
            case 'stripe':
                return [
                    'transaction_id' => $payload['data']['object']['payment_intent'] ?? null,
                    'amount' => ($payload['data']['object']['amount_received'] ?? 0) / 100, // Stripe uses cents
                    'currency' => strtoupper($payload['data']['object']['currency'] ?? 'USD'),
                    'status' => ($payload['data']['object']['status'] ?? '') === 'succeeded' ? 'completed' : 'failed',
                    'gateway' => 'stripe',
                    'timestamp' => now()->toIso8601String(),
                    'raw_payload' => $payload
                ];
                
            case 'bkash':
                return [
                    'transaction_id' => $payload['trxID'] ?? null,
                    'amount' => $payload['amount'] ?? 0,
                    'currency' => $payload['currency'] ?? 'BDT',
                    'status' => ($payload['transactionStatus'] ?? '') === 'Completed' ? 'completed' : 'failed',
                    'gateway' => 'bkash',
                    'timestamp' => now()->toIso8601String(),
                    'raw_payload' => $payload
                ];

            case 'sslcommerz':
                return [
                    'transaction_id' => $payload['val_id'] ?? null,
                    'amount' => $payload['amount'] ?? 0,
                    'currency' => $payload['currency_type'] ?? 'BDT',
                    'status' => ($payload['status'] ?? '') === 'VALID' ? 'completed' : 'failed',
                    'gateway' => 'sslcommerz',
                    'timestamp' => now()->toIso8601String(),
                    'raw_payload' => $payload
                ];

            default:
                throw new \Exception("Unsupported gateway for normalization: {$gateway}");
        }
    }
}
