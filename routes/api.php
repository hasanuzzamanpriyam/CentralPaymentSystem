<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentIntentController;
use App\Http\Controllers\Api\GatewayCredentialController;
use App\Http\Controllers\Api\GatewayWebhookController;
use App\Http\Middleware\IdempotencyMiddleware;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// SDK & Server API endpoints using Project API Key
Route::middleware(['auth.project'])->prefix('projects/{project}')->group(function () {
    Route::get('/gateways', [GatewayCredentialController::class, 'index']);
    Route::get('/gateways/{gateway}/credentials', [GatewayCredentialController::class, 'show']);
});

Route::middleware(['auth:sanctum', IdempotencyMiddleware::class])->group(function () {
    Route::post('/payments/intent', [PaymentIntentController::class, 'store']);
});

// Incoming Webhooks from External Gateways (Stripe, bKash, etc)
Route::post('/webhooks/{gateway}', [GatewayWebhookController::class, 'handleWebhook']);
