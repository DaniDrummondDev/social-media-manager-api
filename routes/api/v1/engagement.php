<?php

use App\Infrastructure\Engagement\Controllers\AutomationRuleController;
use App\Infrastructure\Engagement\Controllers\BlacklistController;
use App\Infrastructure\Engagement\Controllers\CommentController;
use App\Infrastructure\Engagement\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context'])->group(function () {
    // Comments
    Route::get('comments', [CommentController::class, 'index']);
    Route::put('comments/{id}/read', [CommentController::class, 'markAsRead']);
    Route::put('comments/read', [CommentController::class, 'markManyAsRead']);
    Route::post('comments/{id}/reply', [CommentController::class, 'reply']);
    Route::post('comments/{id}/suggest-reply', [CommentController::class, 'suggestReply']);

    // Automation Rules
    Route::post('automation-rules', [AutomationRuleController::class, 'store']);
    Route::get('automation-rules', [AutomationRuleController::class, 'index']);
    Route::put('automation-rules/{id}', [AutomationRuleController::class, 'update']);
    Route::delete('automation-rules/{id}', [AutomationRuleController::class, 'destroy']);
    Route::get('automation-rules/{id}/executions', [AutomationRuleController::class, 'executions']);

    // Blacklist
    Route::get('automation-blacklist', [BlacklistController::class, 'index']);
    Route::post('automation-blacklist', [BlacklistController::class, 'store']);
    Route::delete('automation-blacklist/{id}', [BlacklistController::class, 'destroy']);

    // Webhooks
    Route::post('webhooks', [WebhookController::class, 'store']);
    Route::get('webhooks', [WebhookController::class, 'index']);
    Route::put('webhooks/{id}', [WebhookController::class, 'update']);
    Route::delete('webhooks/{id}', [WebhookController::class, 'destroy']);
    Route::post('webhooks/{id}/test', [WebhookController::class, 'test']);
    Route::get('webhooks/{id}/deliveries', [WebhookController::class, 'deliveries']);
});
