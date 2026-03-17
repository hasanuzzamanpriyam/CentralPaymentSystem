<?php
/**
 * Plugin Name: Central Payment Gateway for WooCommerce
 * Plugin URI: https://centralpaymentsystem.com
 * Description: Accept payments on WooCommerce via the Central Payment Orchestrator (Stripe, bKash, SSLCommerz).
 * Version: 1.0.0
 * Author: Central Payment System
 * License: GPL-2.0+
 * Text Domain: central-payment-gateway
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Define Plugin Constants
define('CPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Core Files
require_once CPG_PLUGIN_DIR . 'includes/class-cpg-client.php';
require_once CPG_PLUGIN_DIR . 'includes/class-cpg-webhook-handler.php';

// Initialize the Plugin
add_action('plugins_loaded', 'cpg_init', 0);

function cpg_init() {
    require_once CPG_PLUGIN_DIR . 'includes/class-wc-gateway-central-payment.php';

    // Register Gateway with WooCommerce
    add_filter('woocommerce_payment_gateways', 'cpg_add_gateway_class');
}

function cpg_add_gateway_class($methods) {
    $methods[] = 'WC_Gateway_Central_Payment';
    return $methods;
}

// Initialize Webhook Handler on WP REST API Init
add_action('rest_api_init', function () {
    $handler = new CPG_Webhook_Handler();
    $handler->register_routes();
});
