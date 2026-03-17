<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Central_Payment extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'central_payment';
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = false; // We don't need custom credit card fields here, it redirects
        $this->method_title = 'Central Payment System';
        $this->method_description = 'Accept payments via Stripe, bKash, and SSLCommerz securely.';

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->environment = $this->get_option('environment');
        $this->api_key = $this->get_option('api_key');
        $this->project_id = $this->get_option('project_id');
        $this->webhook_secret = $this->get_option('webhook_secret');
        // Let the merchant pick the preferred sub-gateway for this specific method, or route dynamically.
        $this->preferred_gateway = $this->get_option('preferred_gateway'); 

        // Save admin settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Central Payment System',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Credit Card / Mobile Money',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay securely via our universal payment switch.',
            ),
            'environment' => array(
                'title'       => 'Environment',
                'type'        => 'select',
                'default'     => 'sandbox',
                'options'     => array(
                    'sandbox'    => 'Sandbox / Test',
                    'production' => 'Production / Live',
                ),
            ),
            'project_id' => array(
                'title'       => 'Project ID',
                'type'        => 'text',
                'description' => 'Your Central Payment Orchestrator Project ID.',
            ),
            'api_key' => array(
                'title'       => 'Project API Key',
                'type'        => 'password',
                'description' => 'sk_...',
            ),
            'webhook_secret' => array(
                'title'       => 'Webhook Secret',
                'type'        => 'password',
                'description' => 'whsec_... (Used to verify incoming payment success notifications)',
            ),
            'preferred_gateway' => array(
                'title'       => 'Default Gateway Route',
                'type'        => 'select',
                'default'     => 'stripe',
                'options'     => array(
                    'stripe'     => 'Stripe',
                    'bkash'      => 'bKash',
                    'sslcommerz' => 'SSLCommerz',
                ),
                'description' => 'Which underlying network should process this payment?',
            )
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        try {
            $client = new CPG_Client($this->api_key, $this->project_id, $this->environment);

            $idempotency_key = $order->get_order_key() . '_' . time();

            $metadata = [
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key(),
                'customer_email' => $order->get_billing_email(),
                'platform' => 'woocommerce'
            ];

            // Convert amount. Orchestrator handles decimals naturally, but WooCommerce might format differently.
            $amount = $order->get_total();
            $currency = $order->get_currency();

            $response = $client->create_payment(
                $amount, 
                $currency, 
                $this->preferred_gateway, 
                $metadata, 
                $idempotency_key
            );

            // The orchestrator typically returns a transaction_id and a checkout_url 
            // (or triggers a redirect to the underlying gateway natively).
            // For now, assume our Sandbox provides a transaction ID that we redirect to for mock checkout.
            
            // In a production system bridging real gateways, the API response would return
            // the Stripe Checkout URL, bKash URL, or SSLCommerz URL directly.
            // Let's assume the API returns `checkout_url`.

            // Save transaction ID to order for reference
            if (isset($response['transaction_id'])) {
                $order->update_meta_data('_cpg_transaction_id', $response['transaction_id']);
                $order->save();
            }

            $redirect_url = isset($response['checkout_url']) 
                ? $response['checkout_url'] 
                : "http://127.0.0.1:8000/demo/checkout/" . $response['transaction_id']; // Sandbox fallback

            return array(
                'result'   => 'success',
                'redirect' => $redirect_url
            );

        } catch (Exception $e) {
            wc_add_notice('Connection Error: ' . $e->getMessage(), 'error');
            return array(
                'result'  => 'fail',
                'message' => $e->getMessage()
            );
        }
    }
}
