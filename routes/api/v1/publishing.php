<?php

use App\Infrastructure\Publishing\Controllers\PublishingController;
use App\Infrastructure\Publishing\Controllers\ScheduledPostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context'])->group(function () {
    // Schedule & Publish
    Route::post('contents/{contentId}/schedule', [PublishingController::class, 'schedule']);
    Route::post('contents/{contentId}/publish-now', [PublishingController::class, 'publishNow']);

    // Calendar (before {id} to avoid route conflict)
    Route::get('scheduled-posts/calendar', [ScheduledPostController::class, 'calendar']);

    // Scheduled Posts CRUD
    Route::get('scheduled-posts', [ScheduledPostController::class, 'index']);
    Route::get('scheduled-posts/{id}', [ScheduledPostController::class, 'show']);
    Route::put('scheduled-posts/{id}', [ScheduledPostController::class, 'update']);
    Route::delete('scheduled-posts/{id}', [ScheduledPostController::class, 'destroy']);
    Route::post('scheduled-posts/{id}/retry', [ScheduledPostController::class, 'retry']);
});
