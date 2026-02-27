<?php

use App\Infrastructure\AIIntelligence\Controllers\AdIntelligenceController;
use App\Infrastructure\PaidAdvertising\Controllers\AdAccountController;
use App\Infrastructure\PaidAdvertising\Controllers\AdAnalyticsController;
use App\Infrastructure\PaidAdvertising\Controllers\AdBoostController;
use App\Infrastructure\PaidAdvertising\Controllers\AudienceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->group(function () {
    Route::middleware('plan.limit:paid_advertising')->group(function () {
        // Ad Accounts
        Route::post('ads/accounts/connect', [AdAccountController::class, 'connect']);
        Route::post('ads/accounts/callback', [AdAccountController::class, 'callback']);
        Route::get('ads/accounts', [AdAccountController::class, 'index']);
        Route::get('ads/accounts/{id}', [AdAccountController::class, 'show']);
        Route::post('ads/accounts/{id}/test', [AdAccountController::class, 'test']);
        Route::delete('ads/accounts/{id}', [AdAccountController::class, 'destroy']);

        // Audiences
        Route::post('ads/audiences', [AudienceController::class, 'store']);
        Route::get('ads/audiences', [AudienceController::class, 'index']);
        Route::get('ads/audiences/{id}', [AudienceController::class, 'show']);
        Route::put('ads/audiences/{id}', [AudienceController::class, 'update']);
        Route::delete('ads/audiences/{id}', [AudienceController::class, 'destroy']);

        // Interest Search
        Route::get('ads/interests/search', [AudienceController::class, 'searchInterests']);

        // Ad Boosts
        Route::post('ads/boosts', [AdBoostController::class, 'store']);
        Route::get('ads/boosts', [AdBoostController::class, 'index']);
        Route::get('ads/boosts/{id}', [AdBoostController::class, 'show']);
        Route::post('ads/boosts/{id}/cancel', [AdBoostController::class, 'cancel']);
        Route::get('ads/boosts/{id}/metrics', [AdBoostController::class, 'metrics']);

        // Analytics
        Route::get('ads/analytics/overview', [AdAnalyticsController::class, 'overview']);
        Route::get('ads/analytics/spending', [AdAnalyticsController::class, 'spending']);
        Route::post('ads/analytics/export', [AdAnalyticsController::class, 'export']);

        // Ad Intelligence
        Route::get('ads/intelligence/insights', [AdIntelligenceController::class, 'insights']);
        Route::get('ads/intelligence/targeting-suggestions', [AdIntelligenceController::class, 'targetingSuggestions']);
    });
});
