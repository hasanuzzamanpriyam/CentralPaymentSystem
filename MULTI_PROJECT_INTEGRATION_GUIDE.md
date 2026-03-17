# Multi-Project Integration Guide

## Making Central Payment System Usable Across Different Stacks

This guide explains how to enhance the Central Payment System to allow users to configure payment methods once and use them across different technology stacks (PHP/Laravel, Node.js, Python, React Native, etc.) using the credentials provided by the system.

## Current Architecture Limitations

The current implementation is tightly coupled to Laravel/PHP applications:

- Payment processing happens in Laravel controllers
- Webhook handling is Laravel-specific
- SDKs/drivers are PHP-only
- No standardized way for external systems to access configured credentials

## Proposed Enhancements for Multi-Stack Integration

### 1. Credential Management API

Add secure API endpoints for external systems to retrieve decrypted credentials:

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    // Get credentials for a specific gateway in a project
    Route::get('/projects/{project}/gateways/{gateway}/credentials',
        [App\Http\Controllers\Api\GatewayCredentialController::class, 'show']);

    // List all configured gateways for a project
    Route::get('/projects/{project}/gateways',
        [App\Http\Controllers\Api\GatewayCredentialController::class, 'index']);
});
```

```php
// app/Http/Controllers/Api/GatewayCredentialController.php
class GatewayCredentialController extends Controller
{
    public function show(Project $project, string $gateway)
    {
        // Verify user owns the project
        $this->authorize('view', $project);

        $gatewayConfig = $project->gateways()
            ->where('gateway_name', $gateway)
            ->where('is_active', true)
            ->firstOrFail();

        // Return decrypted credentials (only to authorized users)
        return response()->json([
            'gateway' => $gateway,
            'credentials' => $gatewayConfig->credentials,
            'is_active' => $gatewayConfig->is_active
        ]);
    }
}
```

### 2. Standardized Credential Format

Define a consistent credential structure that external systems can expect:

```php
// app/Services/Payments/CredentialFormatter.php
class CredentialFormatter
{
    public static function formatForGateway(string $gateway, array $credentials): array
    {
        return match (strtolower($gateway)) {
            'stripe' => [
                'api_key' => $credentials['secret_key'] ?? '',
                'webhook_secret' => $credentials['webhook_secret'] ?? '',
            ],
            'bkash' => [
                'app_key' => $credentials['app_key'] ?? '',
                'app_secret' => $credentials['app_secret'] ?? '',
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? '',
            ],
            'sslcommerz' => [
                'store_id' => $credentials['store_id'] ?? '',
                'store_password' => $credentials['store_password'] ?? '',
                'is_sandbox' => $credentials['is_sandbox'] ?? true,
            ],
            default => throw new InvalidArgumentException("Unsupported gateway: {$gateway}"),
        };
    }
}
```

### 3. Official SDKs for Different Stacks

Create lightweight SDKs that consume the credential API:

#### JavaScript/TypeScript SDK

```bash
npm install @central-payment/system-sdk
```

```javascript
import CentralPayment from '@central-payment/system-sdk';

const client = new CentralPayment({
    apiKey: 'your-project-api-key', // From Project model
    baseUrl: 'https://your-central-payment-system.com/api',
});

// Get Stripe credentials for your project
const stripeCredentials = await client.getGatewayCredentials('stripe');

// Use with official Stripe SDK
import Stripe from 'stripe';
const stripe = new Stripe(stripeCredentials.api_key, {
    apiVersion: '2023-10-16',
    webhookSecret: stripeCredentials.webhook_secret,
});
```

#### Python SDK

```bash
pip install central-payment-sdk
```

```python
from central_payment import CentralPaymentClient

client = CentralPaymentClient(
    api_key="your-project-api-key",
    base_url="https://your-central-payment-system.com/api"
)

# Get Bkash credentials
bkash_creds = client.get_gateway_credentials('bkash')

# Use with official Bkash SDK (hypothetical)
import bkash
bkash_client = bkash.Client(
    app_key=bkash_creds['app_key'],
    app_secret=bkash_creds['app_secret'],
    username=bkash_creds['username'],
    password=bkash_creds['password']
)
```

### 4. Webhook Standardization Service

Create a service that normalizes webhooks from different gateways:

```php
// app/Services/Webhooks/WebhookNormalizer.php
class WebhookNormalizer
{
    public function normalize(array $rawPayload, string $gateway): array
    {
        return match (strtolower($gateway)) {
            'stripe' => $this->normalizeStripe($rawPayload),
            'bkash' => $this->normalizeBkash($rawPayload),
            'sslcommerz' => $this->normalizeSslCommerz($rawPayload),
            default => throw new InvalidArgumentException("Unsupported gateway: {$gateway}"),
        };
    }

    private function normalizeStripe(array $payload): array
    {
        // Extract standard fields from Stripe webhook
        return [
            'transaction_id' => $payload['data']['object']['id'],
            'amount' => $payload['data']['object']['amount_total'] / 100,
            'currency' => strtoupper($payload['data']['object']['currency']),
            'status' => $this->mapStripeStatus($payload['data']['object']['status']),
            'gateway' => 'stripe',
            'raw_payload' => $payload
        ];
    }

    // Similar methods for other gateways...
}
```

### 5. Standardized Webhook Endpoint

Create a single webhook endpoint that external systems can call:

```php
// routes/api.php
Route::post('/webhooks/{gateway}', [GatewayWebhookController::class, 'handle']);

// app/Http/Controllers/GatewayWebhookController.php
class GatewayWebhookController extends Controller
{
    public function handle(Request $request, string $gateway)
    {
        // Verify signature (implementation varies by gateway)
        $isValid = $this->verifySignature($request, $gateway);

        if (!$isValid) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Normalize the webhook payload
        $normalized = app(WebhookNormalizer::class)->normalize(
            $request->all(),
            $gateway
        );

        // Find transaction by gateway transaction ID
        $transaction = Transaction::where('gateway_transaction_id', $normalized['transaction_id'])
            ->firstOrFail();

        // Update transaction status
        $transaction->update([
            'status' => $normalized['status'],
            'metadata' => array_merge(
                $transaction->metadata ?? [],
                ['webhook_payload' => $normalized['raw_payload']]
            )
        ]);

        # Notify merchant webhook if configured
        if ($transaction->project?->webhook_url) {
            app(NotifyMerchantWebhookJob::class)->dispatch(
                $transaction,
                $transaction->project
            );
        }

        return response()->json(['status' => 'processed']);
    }
}
```

### 6. Documentation for External Developers

Add comprehensive documentation showing how to integrate with different stacks:

````markdown
# Integrating with Central Payment System

## Getting Your Credentials

1. Create a project in the Central Payment System dashboard
2. Configure your preferred payment gateways (Stripe, Bkash, etc.)
3. Note your Project API Key (found in project settings)

## Using Credentials in Your Application

### JavaScript Example

```javascript
import CentralPayment from '@central-payment/system-sdk';

const cp = new CentralPayment({
    apiKey: 'your-project-api-key',
    baseUrl: 'https://your-central-payment-system.com',
});

// Get Stripe credentials
const stripeCreds = await cp.getGatewayCredentials('stripe');

// Initialize Stripe
import { loadStripe } from '@stripe/stripe-js';
const stripePromise = loadStripe(stripeCreds.api_key);

// Handle webhooks (verification required)
// POST https://your-domain.com/webhooks/stripe
// (Your system handles signature verification)
```
````

### Python Example

```python
from central_payment import CentralPaymentClient

client = CentralPaymentClient(
    api_key="your-project-api-key",
    base_url="https://your-central-payment-system.com"
)

# Get credentials
bkash_creds = client.get_gateway_credentials('bkash')

# Use with your preferred Bkash integration method
```

## Webhook Handling

The Central Payment System provides normalized webhooks:

Endpoint: `POST https://your-central-payment-system.com/api/webhooks/{gateway}`

The system will:

1. Verify webhook signatures
2. Normalize payload to standard format
3. Update transaction status
4. Notify your application's webhook URL (if configured)

Standard webhook payload format:

```json
{
    "transaction_id": "txn_123",
    "amount": 100.5,
    "currency": "USD",
    "status": "completed",
    "gateway": "stripe",
    "timestamp": "2023-06-15T10:30:00Z"
}
```

```

## Implementation Plan

### Phase 1: Credential API (Immediate)
- Add credential retrieval endpoints
- Implement credential formatting service
- Add proper authorization checks

### Phase 2: SDK Development (Short-term)
- Create JavaScript/TypeScript SDK
- Create Python SDK
- Document usage examples

### Phase 3: Webhook Standardization (Medium-term)
- Implement webhook normalizer service
- Create standardized webhook endpoint
- Add webhook signature verification utilities

### Phase 4: Documentation & Examples (Ongoing)
- Create integration guides for popular stacks
- Add sample applications (React, Vue, Node.js, etc.)
- Provide Postman/OpenAPI collections

## Security Considerations

1. **Credential Access**: Only authenticated project owners can retrieve credentials
2. **Rate Limiting**: Implement rate limiting on credential API endpoints
3. **Audit Logging**: Log all credential access attempts
4. **Environment Isolation**: Ensure production credentials never expose test/live mix-ups
5. **Webhook Security**: Maintain gateway-specific signature verification

## Benefits of This Approach

1. **True Multi-Stack Support**: Developers can use any language/framework
2. **Single Source of Truth**: Credentials managed in one place
3. **Reduced Integration Complexity**: No need to manage multiple credential sets
4. **Consistent Experience**: Same credentials work across web, mobile, desktop
5. **Centralized Management**: Rotate credentials once, update everywhere
6. **Future-Proof**: Easy to add new payment gateways or SDKs

## Conclusion

By implementing these enhancements, the Central Payment System evolves from a Laravel-specific payment processor to a true multi-platform payment infrastructure that developers can integrate into any application stack while maintaining secure credential management and standardized interfaces.
```
