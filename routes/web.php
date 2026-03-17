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
        
        // Project routes
        Route::post('projects', [\App\Http\Controllers\ProjectController::class, 'store'])->name('projects.store');
        Route::post('projects/{project}/webhook', [\App\Http\Controllers\ProjectController::class, 'updateWebhook'])->name('projects.webhook.update');
        Route::post('projects/{project}/api-key', [\App\Http\Controllers\ProjectController::class, 'regenerateApiKey'])->name('projects.apikey.regenerate');
        Route::post('projects/{project}/gateway', [\App\Http\Controllers\ProjectController::class, 'configureGateway'])->name('projects.gateway.configure');
    });

    // Demo Store & Mock Gateway
    Route::get('/demo/store', [\App\Http\Controllers\DemoStoreController::class, 'index'])->name('demo.store');
    Route::get('/demo/checkout/{transaction}', [\App\Http\Controllers\DemoStoreController::class, 'checkout'])->name('demo.checkout');
});

require __DIR__.'/settings.php';
