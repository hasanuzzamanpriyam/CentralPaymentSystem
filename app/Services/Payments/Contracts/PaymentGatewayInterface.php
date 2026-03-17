<?php

namespace App\Services\Payments\Contracts;

use App\Models\Transaction;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment.
     *
     * @param Transaction $transaction
     * @return array
     */
    public function initializePayment(Transaction $transaction): array;

    /**
     * Verify a payment by its gateway transaction ID.
     *
     * @param string $gatewayTxId
     * @return array
     */
    public function verifyPayment(string $gatewayTxId): array;

    /**
     * Refund a payment.
     *
     * @param Transaction $transaction
     * @return array
     */
    public function refund(Transaction $transaction): array;

    /**
     * Get the required credential field definitions for this gateway.
     *
     * @return array
     */
    public function getRequiredCredentials(): array;

    /**
     * Determine if this gateway supports automated API refunds.
     *
     * @return bool
     */
    public function supportsRefunds(): bool;

    /**
     * Standardize the raw gateway response into a predictable format for SDKs.
     *
     * @param mixed $gatewayResponse
     * @return array
     */
    public function getStandardizedResponse(mixed $gatewayResponse): array;
}
