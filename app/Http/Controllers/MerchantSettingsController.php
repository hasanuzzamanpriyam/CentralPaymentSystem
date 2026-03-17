<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class MerchantSettingsController extends Controller
{
    public function updateWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => ['nullable', 'url', 'max:255'],
        ]);

        $credential = $request->user()->merchantCredential()->firstOrCreate(['user_id' => $request->user()->id]);
        $credential->update([
            'webhook_url' => $request->webhook_url,
        ]);

        return back()->with('success', 'Webhook URL updated successfully.');
    }

    public function regenerateApiKey(Request $request)
    {
        $rawApiKey = 'sk_test_' . Str::random(32);
        $rawWebhookSecret = 'whsec_' . Str::random(32);

        $credential = $request->user()->merchantCredential()->firstOrCreate(['user_id' => $request->user()->id]);
        $credential->update([
            'api_key' => $rawApiKey, // Accessor processes the hash if $casts is used
            'webhook_secret' => $rawWebhookSecret,
        ]);

        return back()->with('raw_credentials', [
            'api_key' => $rawApiKey,
            'webhook_secret' => $rawWebhookSecret,
        ]);
    }
}
