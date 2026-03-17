<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $type = request()->user()->account_type;
        return redirect()->route("dashboard.{$type}");
    })->name('dashboard');

    Route::middleware([\App\Http\Middleware\CheckAccountType::class . ':personal'])->group(function () {
        Route::get('dashboard/personal', [\App\Http\Controllers\DashboardController::class, 'personal'])->name('dashboard.personal');
    });

    Route::middleware([\App\Http\Middleware\CheckAccountType::class . ':merchant'])->group(function () {
        Route::get('dashboard/merchant', [\App\Http\Controllers\DashboardController::class, 'merchant'])->name('dashboard.merchant');
        Route::post('dashboard/merchant/webhook', [\App\Http\Controllers\MerchantSettingsController::class, 'updateWebhook'])->name('merchant.webhook.update');
        Route::post('dashboard/merchant/api-key', [\App\Http\Controllers\MerchantSettingsController::class, 'regenerateApiKey'])->name('merchant.apikey.regenerate');
    });

    // Demo Store & Mock Gateway
    Route::get('/demo/store', [\App\Http\Controllers\DemoStoreController::class, 'index'])->name('demo.store');
    Route::get('/demo/checkout/{transaction}', [\App\Http\Controllers\DemoStoreController::class, 'checkout'])->name('demo.checkout');
});

require __DIR__.'/settings.php';
