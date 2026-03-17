<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Personal User
        $personalUser = User::firstOrCreate(
            ['email' => 'personal@example.com'],
            [
                'name' => 'Personal Account',
                'password' => bcrypt('password'),
                'account_type' => 'personal',
            ]
        );
        $personalUser->wallet()->firstOrCreate(['currency' => 'BDT'], ['balance' => 0]);

        // Merchant User
        $merchantUser = User::firstOrCreate(
            ['email' => 'merchant@example.com'],
            [
                'name' => 'Merchant Account',
                'password' => bcrypt('password'),
                'account_type' => 'merchant',
            ]
        );
        $merchantUser->merchantCredential()->firstOrCreate([], [
            'api_key' => 'sk_test_' . Str::random(32),
            'webhook_secret' => 'whsec_' . Str::random(32),
            'webhook_url' => 'http://localhost/api/demo/webhook-receiver',
        ]);
        $merchantUser->wallet()->firstOrCreate(['currency' => 'BDT'], ['balance' => 0]);
        
    }
}
