const axios = require('axios');
const { WebhookVerifier } = require('./webhooks/verifier');

class CentralPaymentClient {
    /**
     * Initialize the Central Payment SDK Client
     * 
     * @param {Object} options 
     * @param {string} options.apiKey Your Project API Key (sk_...)
     * @param {string} options.projectId Your Project ID
     * @param {string} options.environment 'sandbox' or 'production'
     */
    constructor({ apiKey, projectId, environment = 'sandbox' }) {
        if (!apiKey || !projectId) {
            throw new Error('apiKey and projectId are required to initialize CentralPaymentClient');
        }

        this.apiKey = apiKey;
        this.projectId = projectId;
        this.baseURL = environment === 'production' 
            ? 'https://api.centralpayment.com/api' 
            : 'http://127.0.0.1:8000/api';

        this.http = axios.create({
            baseURL: this.baseURL,
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'X-Project-Id': this.projectId,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
    }

    /**
     * Fetch active gateways and their required credential formats for this project.
     * @returns {Promise<Array>}
     */
    async getGateways() {
        const response = await this.http.get(`/projects/${this.projectId}/gateways`);
        return response.data.gateways;
    }

    /**
     * Create a new payment intent
     * 
     * @param {Object} params 
     * @param {number} params.amount Amount to charge
     * @param {string} params.currency Currency code (e.g. 'USD', 'BDT')
     * @param {string} params.gateway Which gateway to route this payment to (e.g. 'stripe', 'bkash')
     * @param {Object} params.metadata Optional key-value pairs to store with the transaction
     * @param {string} params.idempotencyKey Optional UUID to prevent duplicate charges
     * @returns {Promise<Object>} The checkout URL and transaction reference
     */
    async createPayment({ amount, currency, gateway, metadata = {}, idempotencyKey }) {
        const headers = {};
        if (idempotencyKey) {
            headers['Idempotency-Key'] = idempotencyKey;
        }

        const response = await this.http.post('/payments/intent', {
            amount,
            currency,
            gateway,
            project_id: this.projectId,
            metadata
        }, { headers });

        return response.data;
    }

    /**
     * Utility method to verify incoming webhooks from the Central Payment System
     */
    verifyWebhook(rawPayload, signature, webhookSecret) {
        return WebhookVerifier.verifySignature(rawPayload, signature, webhookSecret);
    }
}

module.exports = { CentralPaymentClient };
