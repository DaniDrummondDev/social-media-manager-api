<?php

use App\Infrastructure\Campaign\Controllers\CampaignController;
use App\Infrastructure\Campaign\Controllers\ContentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->group(function () {
    // Campaigns
    Route::post('campaigns', [CampaignController::class, 'store']);
    Route::get('campaigns', [CampaignController::class, 'index']);
    Route::get('campaigns/{id}', [CampaignController::class, 'show']);
    Route::put('campaigns/{id}', [CampaignController::class, 'update']);
    Route::delete('campaigns/{id}', [CampaignController::class, 'destroy']);
    Route::post('campaigns/{id}/duplicate', [CampaignController::class, 'duplicate']);
    Route::post('campaigns/{id}/restore', [CampaignController::class, 'restore']);

    // Contents (nested under campaigns)
    Route::post('campaigns/{campaignId}/contents', [ContentController::class, 'store']);
    Route::get('campaigns/{campaignId}/contents', [ContentController::class, 'index']);

    // Contents (standalone)
    Route::get('contents/{id}', [ContentController::class, 'show']);
    Route::put('contents/{id}', [ContentController::class, 'update']);
    Route::delete('contents/{id}', [ContentController::class, 'destroy']);
});
