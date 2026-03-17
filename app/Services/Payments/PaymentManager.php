<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Drivers\BkashDriver;
use App\Services\Payments\Drivers\SslCommerzDriver;
use App\Services\Payments\Drivers\StripeDriver;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Resolve the payment gateway driver based on the gateway name.
     *
     * @param string $gatewayName
     * @param array|null $credentials
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException
     */
    public function resolve(string $gatewayName, ?array $credentials = null): PaymentGatewayInterface
    {
        return match (strtolower($gatewayName)) {
            'stripe' => new StripeDriver($credentials),
            'bkash' => new BkashDriver($credentials),
            'sslcommerz' => new SslCommerzDriver($credentials),
            default => throw new InvalidArgumentException("Unsupported payment gateway: {$gatewayName}"),
        };
    }
}
