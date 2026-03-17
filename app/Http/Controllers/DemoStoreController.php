<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DemoStoreController extends Controller
{
    /**
     * Show the Demo Store UI where a Merchant or user can click to "buy" something.
     */
    public function index(Request $request)
    {
        // For the demo store, we really just need a UI to trigger the API.
        // We'll pass the merchant credentials if the logged in user is a merchant,
        // just to make it easy to test using their own API key.
        // If they are personal, we'll let them know they need a merchant's API key.
        
        $user = $request->user();
        $apiKey = null;
        
        if ($user->account_type === 'merchant') {
            // Note: in a real app, the store would NEVER expose the secret API key to the frontend.
            // But this is just a local sandbox to simulate the MERN backend calling the Orchestrator API.
            // We just need ANY valid token to make the API call. Since Sanctum protects the endpoint,
            // we'll actually rely on the session auth to hit the API, or we can use the merchant's key if testing externally.
        }

        return Inertia::render('Demo/Store', [
            'isMerchant' => $user->account_type === 'merchant',
        ]);
    }

    /**
     * Show the Mock Gateway UI.
     * This is where they "pay" and we simulate Stripe's webhook.
     */
    public function checkout(Request $request, Transaction $transaction)
    {
        // This simulates a user landing on a Stripe Checkout page.
        return Inertia::render('Demo/GatewayCheckout', [
            'transaction' => $transaction,
        ]);
    }
}
