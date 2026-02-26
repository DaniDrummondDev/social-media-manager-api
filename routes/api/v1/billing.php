<?php

use App\Infrastructure\Billing\Controllers\BillingController;
use App\Infrastructure\Billing\Controllers\PlanController;
use App\Infrastructure\Billing\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Public — no auth required
Route::get('plans', [PlanController::class, 'index']);

// Stripe webhook — no auth, validated by signature
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle']);

// Authenticated billing routes
Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->group(function () {
    Route::get('billing/subscription', [BillingController::class, 'subscription']);
    Route::get('billing/usage', [BillingController::class, 'usage']);
    Route::get('billing/invoices', [BillingController::class, 'invoices']);

    // Owner-only actions
    Route::middleware('role:owner')->group(function () {
        Route::post('billing/checkout', [BillingController::class, 'checkout']);
        Route::post('billing/portal', [BillingController::class, 'portal']);
        Route::post('billing/cancel', [BillingController::class, 'cancel']);
        Route::post('billing/reactivate', [BillingController::class, 'reactivate']);
    });
});
