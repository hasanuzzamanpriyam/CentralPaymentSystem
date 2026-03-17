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
     * Handle incoming webhooks from any supported external gateway.
     */
    public function handleWebhook(Request $request, string $gateway, \App\Services\Webhooks\WebhookNormalizer $normalizer)
    {
        $payloadRaw = $request->getContent();
        $payloadArray = json_decode($payloadRaw, true) ?: $request->all();

        try {
            // Normalize payload to find transaction reference
            $normalized = $normalizer->normalize($payloadArray, $gateway);
        } catch (\Exception $e) {
            Log::error("Webhook Normalization Failed: " . $e->getMessage());
            return response()->json(['error' => 'Unsupported gateway'], 400);
        }

        $transactionId = $normalized['transaction_id'];

        if (!$transactionId) {
            Log::error("Webhook: Could not find transaction ID in payload for $gateway.");
            return response()->json(['error' => 'Transaction ID missing'], 400);
        }

        try {
            DB::beginTransaction();
            $transaction = Transaction::where('id', '=', $transactionId)->lockForUpdate()->first();

            if (!$transaction) {
                DB::rollBack();
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            if ($transaction->status === 'completed') {
                DB::rollBack();
                return response()->json(['status' => 'already_processed'], 200);
            }

            // Note: Signature verification should theoretically occur here using the 
            // project's specific gateway configs ($transaction->project->gateways).
            // For the sandbox MVP, we accept the mock payload if it parses cleanly.

            $wallet = $transaction->user->wallet()->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return response()->json(['error' => 'Wallet not found'], 404);
            }

            if ($normalized['status'] === 'completed') {
                $wallet->balance += $normalized['amount'] > 0 ? $normalized['amount'] : $transaction->amount;
                $wallet->save();
            }

            $transaction->status = $normalized['status'];
            $transaction->gateway_transaction_id = $normalized['raw_payload']['id'] ?? ($normalized['raw_payload']['trxID'] ?? time());
            $transaction->save();
            DB::commit();

            // Notify Merchant
            if ($transaction->type === 'merchant_payment' && $transaction->project) {
                $project = $transaction->project;
                if ($project->webhook_url) {
                    // Pass the normalized payload to the outbound job
                    DispatchMerchantWebhookJob::dispatch($transaction, $project, $normalized);
                }
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook Processing Error: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
