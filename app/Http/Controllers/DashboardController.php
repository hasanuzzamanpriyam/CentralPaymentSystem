<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function personal(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);
        
        $transactions = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Dashboard/Personal', [
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }

    public function merchant(Request $request)
    {
        $user = $request->user();
        
        $merchantCredential = $user->merchantCredential()->firstOrCreate(['user_id' => $user->id]);

        $totalVolume = $user->transactions()
            ->where('status', 'completed')
            ->sum('amount');

        $totalTransactions = $user->transactions()->count();

        // Check if session has flash data for raw credentials (from just regenerating)
        $rawCredentials = session('raw_credentials');

        return Inertia::render('Dashboard/Merchant', [
            'totalVolume' => $totalVolume,
            'totalTransactions' => $totalTransactions,
            'webhookUrl' => $merchantCredential->webhook_url,
            'hasApiKey' => !empty($merchantCredential->api_key),
            'rawCredentials' => $rawCredentials,
        ]);
    }
}
