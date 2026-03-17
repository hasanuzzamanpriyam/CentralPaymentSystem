<?php

if (!defined('ABSPATH')) {
    exit;
}

class CPG_Webhook_Handler {

    public function register_routes() {
        register_rest_route('central-payment/v1', '/webhook', array(
            'methods'  => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Validation happens inside via HMAC
        ));
    }

    public function handle_webhook(WP_REST_Request $request) {
        $body = $request->get_body();
        $signature = $request->get_header('x_orchestrator_signature');

        if (empty($signature)) {
            return new WP_REST_Response('Missing Signature Header', 400);
        }

        // Fetch the webhook secret from the gateway settings
        $settings = get_option('woocommerce_central_payment_settings', []);
        $secret = isset($settings['webhook_secret']) ? $settings['webhook_secret'] : '';

        if (empty($secret)) {
            return new WP_REST_Response('Webhook secret not configured in WooCommerce settings.', 500);
        }

        // Verify HMAC SHA-256 Signature
        $expected_signature = hash_hmac('sha256', $body, $secret);
        
        if (!hash_equals($expected_signature, $signature)) {
            return new WP_REST_Response('Invalid webhook signature.', 401);
        }

        $payload = json_decode($body, true);

        if (!$payload || !isset($payload['transaction_id'])) {
            return new WP_REST_Response('Invalid payload structure.', 400);
        }

        // The orchestrator pushes out standard { transaction_id, status, amount }
        $cpg_transaction_id = $payload['transaction_id'];
        $status = $payload['status'];

        // Find the WooCommerce Order
        global $wpdb;
        
        // Use WP Meta Query to find the order by our saved transaction ID
        $order_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_cpg_transaction_id' AND meta_value = %s
            LIMIT 1
        ", $cpg_transaction_id));

        if (!$order_id) {
            return new WP_REST_Response('Order not found for transaction ID.', 404);
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_REST_Response('Invalid order object.', 500);
        }

        if ($order->has_status('completed') || $order->has_status('processing')) {
            return new WP_REST_Response('Order already processed.', 200);
        }

        if ($status === 'completed') {
            $order->payment_complete($cpg_transaction_id);
            $order->add_order_note(sprintf('Payment approved via Central Payment System. Transaction ID: %s', $cpg_transaction_id));
        } else {
            $order->update_status('failed', 'Central Payment System reported the payment failed or was declined.');
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    }
}
