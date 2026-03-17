<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentIntentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, PaymentManager $paymentManager)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'gateway' => ['required', 'string', 'in:stripe,bkash,sslcommerz'],
            'metadata' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        // 1. Begin Database Transaction
        DB::beginTransaction();
        try {
            // Lock the user's wallet for pessimistic locking
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();

            // 2. Create Pending Transaction Record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'merchant_payment',
                'amount' => $validated['amount'],
                'gateway' => $validated['gateway'],
                'status' => 'pending',
                'metadata' => $validated['metadata'] ?? null,
            ]);

            // 3. Resolve the Gateway Driver
            $driver = $paymentManager->resolve($validated['gateway']);

            // 4. Initialize Payment via Strategy
            $response = $driver->initializePayment($transaction);

            if (! $response['success']) {
                throw new \Exception('Payment gateway initialization failed.');
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'message' => 'Payment intent created successfully.',
                'transaction_id' => $transaction->id,
                'checkout_url' => $response['checkout_url'] ?? null,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
