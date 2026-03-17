const crypto = require('crypto');

class WebhookVerifier {
    /**
     * Verify the HMAC SHA-256 signature of an incoming webhook.
     * 
     * @param {string} rawPayload The stringified JSON payload
     * @param {string} signature Header value from X-Orchestrator-Signature
     * @param {string} webhookSecret Your project's webhook secret starting with "whsec_"
     * @returns {boolean} True if signature is valid
     */
    static verifySignature(rawPayload, signature, webhookSecret) {
        if (!signature || !webhookSecret) return false;

        const expectedSignature = crypto
            .createHmac('sha256', webhookSecret)
            .update(rawPayload)
            .digest('hex');

        // Prevent timing attacks
        return crypto.timingSafeEqual(
            Buffer.from(signature),
            Buffer.from(expectedSignature)
        );
    }
}

module.exports = { WebhookVerifier };
