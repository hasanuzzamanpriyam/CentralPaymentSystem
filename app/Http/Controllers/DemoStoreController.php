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
        $user = $request->user();
        $projectId = null;
        
        if ($user->account_type === 'merchant') {
            // Get the first project for demo purposes
            $project = $user->projects()->first();
            if ($project) {
                $projectId = $project->id;
            }
        }

        return Inertia::render('Demo/Store', [
            'isMerchant' => $user->account_type === 'merchant',
            'projectId' => $projectId,
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
