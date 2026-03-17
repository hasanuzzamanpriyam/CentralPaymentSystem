<?php

if (!defined('ABSPATH')) {
    exit;
}

class CPG_Client {
    private $api_key;
    private $project_id;
    private $base_url;

    public function __construct($api_key, $project_id, $environment = 'sandbox') {
        $this->api_key = $api_key;
        $this->project_id = $project_id;
        $this->base_url = $environment === 'production' 
            ? 'https://api.centralpayment.com/api' 
            : 'http://127.0.0.1:8000/api';
    }

    private function request($method, $endpoint, $body = null, $headers = []) {
        $url = $this->base_url . $endpoint;
        
        $default_headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'X-Project-Id'  => $this->project_id,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        $args = [
            'method'  => $method,
            'timeout' => 45,
            'headers' => array_merge($default_headers, $headers),
        ];

        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code >= 400) {
            $error_message = isset($response_body['message']) ? $response_body['message'] : 'Unknown error occurred.';
            throw new Exception("API Error ($response_code): $error_message");
        }

        return $response_body;
    }

    public function create_payment($amount, $currency, $gateway, $metadata = [], $idempotency_key = null) {
        $headers = [];
        if ($idempotency_key) {
            $headers['Idempotency-Key'] = $idempotency_key;
        }

        $body = [
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => $gateway,
            'project_id' => $this->project_id,
            'metadata' => $metadata
        ];

        return $this->request('POST', '/payments/intent', $body, $headers);
    }
}
