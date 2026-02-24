<?php

use App\Infrastructure\ContentAI\Controllers\AIController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context'])->prefix('ai')->group(function () {
    Route::post('generate-title', [AIController::class, 'generateTitle']);
    Route::post('generate-description', [AIController::class, 'generateDescription']);
    Route::post('generate-hashtags', [AIController::class, 'generateHashtags']);
    Route::post('generate-content', [AIController::class, 'generateContent']);
    Route::get('settings', [AIController::class, 'getSettings']);
    Route::put('settings', [AIController::class, 'updateSettings']);
    Route::get('history', [AIController::class, 'history']);
});
