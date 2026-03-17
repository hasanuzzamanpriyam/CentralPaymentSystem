<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentIntentController;
use App\Http\Middleware\IdempotencyMiddleware;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', IdempotencyMiddleware::class])->group(function () {
    Route::post('/payments/intent', [PaymentIntentController::class, 'store']);
});

// Incoming Webhooks from External Gateways (Stripe, bKash, etc)
// Note: These must not be protected by auth:sanctum because the webhook comes from Stripe.
Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\GatewayWebhookController::class, 'handleStripeWebhook']);
