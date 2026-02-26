<?php

use App\Infrastructure\ContentAI\Controllers\AIController;
use App\Infrastructure\ContentAI\Controllers\ContentAdaptationController;
use App\Infrastructure\ContentAI\Controllers\GenerationFeedbackController;
use App\Infrastructure\ContentAI\Controllers\PromptExperimentController;
use App\Infrastructure\ContentAI\Controllers\PromptTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->prefix('ai')->group(function () {
    Route::post('generate-title', [AIController::class, 'generateTitle']);
    Route::post('generate-description', [AIController::class, 'generateDescription']);
    Route::post('generate-hashtags', [AIController::class, 'generateHashtags']);
    Route::post('generate-content', [AIController::class, 'generateContent']);
    Route::get('settings', [AIController::class, 'getSettings']);
    Route::put('settings', [AIController::class, 'updateSettings']);
    Route::get('history', [AIController::class, 'history']);
    Route::post('adapt-content', [ContentAdaptationController::class, 'adaptContent']);

    // Generation Feedback (AI Learning — N1)
    Route::post('feedback', [GenerationFeedbackController::class, 'store']);

    // Prompt Templates (AI Learning — N3)
    Route::post('prompt-templates', [PromptTemplateController::class, 'store']);

    // Prompt Experiments / A/B Testing (AI Learning — N3)
    Route::post('prompt-experiments', [PromptExperimentController::class, 'store']);
    Route::post('prompt-experiments/{experimentId}/evaluate', [PromptExperimentController::class, 'evaluate']);
});
