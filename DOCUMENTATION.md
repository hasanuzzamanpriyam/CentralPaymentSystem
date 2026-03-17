# Central Payment System

This repository provides a comprehensive Payment Switch / Orchestrator. 

## 🚀 Features
- **Multi-Tenant Architecture**: Merchants manage unlimited "Projects" isolating API keys and configurations.
- **Dynamic Gateways**: Route payments securely to Stripe, bKash, SSLCommerz using the Strategy Pattern. Let merchants plug in specific credentials per project!
- **Idempotency Engine**: Prevents double-charging if connection drops.
- **Centralized Webhooks**: Ingests completely different webhook structures from Stripe/bKash and "normalizes" them to a single standard schema before pushing to the Merchant application using HMAC SHA-256 signatures.

---

## 💻 1. Official SDKs

We provide official SDKs to streamline your integration. 
You can find them inside the `/packages/` directory of this repo.

### JavaScript / TypeScript Node.js SDK
Available in `packages/system-sdk-js`.

```javascript
const { CentralPaymentClient } = require('@central-payment/system-sdk');

const client = new CentralPaymentClient({
    apiKey: 'sk_123456789',
    projectId: 5,
    environment: 'sandbox' 
});

// 1. Create Payment Intent
const session = await client.createPayment({
    amount: 1500.50,
    currency: 'BDT',
    gateway: 'bkash',
    metadata: { order_id: '123' }
});

console.log("Redirect User To:", session.checkout_url);

// 2. Verify Incoming Webhooks securely
app.post('/api/webhook', express.raw({type: 'application/json'}), (req, res) => {
    const isValid = client.verifyWebhook(
        req.body, 
        req.headers['x-orchestrator-signature'], 
        'whsec_abc123'
    );
    if (!isValid) return res.status(401).send('Invalid');
    
    // Process JSON...
});
```

### Python SDK
Available in `packages/system-sdk-python`.

```python
from central_payment import CentralPaymentClient

client = CentralPaymentClient(
    api_key='sk_123456789', 
    project_id='5', 
    environment='sandbox'
)

# 1. Create Payment
intent = client.create_payment(
    amount=150.00,
    currency='USD',
    gateway='stripe',
    metadata={"order_id": "#4519"}
)
print("Checkout URL:", intent.get('checkout_url'))

# 2. Verify Webhook
is_valid = CentralPaymentClient.verify_webhook(
    raw_payload=request.data,
    signature=request.headers.get('X-Orchestrator-Signature'),
    webhook_secret='whsec_abc123'
)
```

---

## 🛍️ 2. WordPress & WooCommerce Integration

No coding is required for WordPress users! We provide a zero-configuration WooCommerce Payment Gateway Plugin in `packages/central-payment-gateway-wp/`.

### Installation
1. Zip the `central-payment-gateway-wp` folder.
2. Go to your WordPress Admin -> Plugins -> Add New -> Upload Plugin.
3. Upload `central-payment-gateway-wp.zip` and Activate.

### Configuration
1. In WordPress, navigate to **WooCommerce -> Settings -> Payments**.
2. Click on **Central Payment System**.
3. Enable the gateway.
4. Input your `Project ID`, `API Key`, and `Webhook Secret` from the Central Payment Merchant Dashboard.
5. Select the **Default Gateway Route** (Stripe, bKash, SSLCommerz). All WooCommerce checkouts will automatically route through your Centralized Switch!

### Security & Compliance
- The plugin utilizes WordPress nonces automatically.
- Webhooks are securely ingested via a custom REST API endpoint (`/wp-json/central-payment/v1/webhook`).
- HMAC SHA-256 validation is enforced. WooCommerce orders only mark as `Processing/Completed` if the signature cryptographically corresponds to your `Webhook Secret`.

---

## 🛠️ 3. Direct API Integration (cURL)

If you don't use WordPress or our SDKs, you can talk to the REST API directly.

### Create Payment Intent
```bash
curl -X POST "https://api.centralpayment.com/api/payments/intent" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer sk_your_api_key_here" \
  -H "X-Project-Id: 5" \
  -H "Idempotency-Key: ui_d4564" \
  -d '{
    "amount": 100.50,
    "currency": "USD",
    "gateway": "stripe",
    "metadata": { "user_id": 991 }
  }'
```

The system will synchronously initialize the remote gateway using the credentials configured for Project #5, and return the `transaction_id` and nested `gateway_checkout_url`.
