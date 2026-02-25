<?php

use App\Infrastructure\Analytics\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context'])->group(function () {
    // Overview
    Route::get('analytics/overview', [AnalyticsController::class, 'overview']);

    // Network Analytics
    Route::get('analytics/networks/{provider}', [AnalyticsController::class, 'network']);

    // Content Analytics
    Route::get('analytics/contents/{contentId}', [AnalyticsController::class, 'content']);

    // Exports
    Route::post('analytics/exports', [AnalyticsController::class, 'export']);
    Route::get('analytics/exports', [AnalyticsController::class, 'listExports']);
    Route::get('analytics/exports/{exportId}', [AnalyticsController::class, 'showExport']);
});
