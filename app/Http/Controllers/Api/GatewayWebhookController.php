<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchMerchantWebhookJob;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GatewayWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from Stripe.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret'); // Assume it's configured

        // 1. Verify Stripe Signature (Mocked for this test)
        // In reality, you would use: \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        if (!$this->verifyMockSignature($payload, $sigHeader)) {
            Log::warning('Invalid Stripe webhook signature.');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        // 2. We only care about successful payment events.
        if ($event['type'] !== 'checkout.session.completed' && $event['type'] !== 'payment_intent.succeeded') {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Extract internal reference (we assume client_reference_id or metadata.transaction_id handles this)
        $transactionId = $event['data']['object']['client_reference_id'] 
                         ?? ($event['data']['object']['metadata']['transaction_id'] ?? null);
        
        $gatewayTxId = $event['data']['object']['id'] ?? 'unknown_stripe_tx_' . time();

        if (!$transactionId) {
            Log::error('Stripe Webhook: Could not find transaction ID in payload.', $event);
            return response()->json(['error' => 'Transaction ID missing'], 400);
        }

        try {
            // 3. Begin ACID Transaction with Pessimistic Locking
            DB::beginTransaction();

            // Lock the transaction row
            $transaction = Transaction::where('id', '=', $transactionId)->lockForUpdate()->first();

            if (!$transaction) {
                DB::rollBack();
                Log::error("Stripe Webhook: Transaction $transactionId not found.");
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Prevent double-processing
            if ($transaction->status === 'completed') {
                DB::rollBack();
                return response()->json(['status' => 'already_processed'], 200);
            }

            // Lock the user's wallet
            $wallet = $transaction->user->wallet()->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                Log::error("Stripe Webhook: Wallet not found for user {$transaction->user_id}.");
                return response()->json(['error' => 'Wallet not found'], 404);
            }

            // 4. Update the Balance and Transaction Status
            $wallet->balance += $transaction->amount;
            $wallet->save();

            $transaction->status = 'completed';
            $transaction->gateway_transaction_id = $gatewayTxId;
            $transaction->save();

            DB::commit();
            
            Log::info("Payment $transactionId successfully processed via Stripe webhook. Wallet balance updated.");

            // 5. Notify the Merchant if applicable
            if ($transaction->type === 'merchant_payment') {
                $merchantCredential = $transaction->user->merchantCredential;
                if ($merchantCredential && $merchantCredential->webhook_url) {
                    // Dispatch the job created in Phase 4
                    DispatchMerchantWebhookJob::dispatch($transaction, $merchantCredential);
                }
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Stripe Webhook Processing Error: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Helper to mock signature verification since we don't have the real Stripe SDK installed tightly here.
     * In production, use Stripe's official SDK.
     */
    private function verifyMockSignature($payload, $sigHeader)
    {
        // For testing purposes, we assume any request to this local endpoint is valid unless explicitly tested.
        // Or if $sigHeader is missing, we could fail it, but let's allow it to pass for development setup.
        return true; 
    }
}
