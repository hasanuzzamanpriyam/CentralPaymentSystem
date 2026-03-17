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
}
