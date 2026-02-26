<?php

use App\Infrastructure\AIIntelligence\Controllers\AudienceInsightsController;
use App\Infrastructure\AIIntelligence\Controllers\BestTimesController;
use App\Infrastructure\AIIntelligence\Controllers\BrandSafetyController;
use App\Infrastructure\AIIntelligence\Controllers\BrandSafetyRuleController;
use App\Infrastructure\AIIntelligence\Controllers\CalendarSuggestionController;
use App\Infrastructure\AIIntelligence\Controllers\ContentGapAnalysisController;
use App\Infrastructure\AIIntelligence\Controllers\ContentProfileController;
use App\Infrastructure\AIIntelligence\Controllers\PerformancePredictionController;
use App\Infrastructure\AIIntelligence\Controllers\PredictionAccuracyController;
use App\Infrastructure\AIIntelligence\Controllers\StyleProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->group(function () {
    // Best Time to Post
    Route::get('ai-intelligence/best-times', [BestTimesController::class, 'index']);
    Route::get('ai-intelligence/best-times/heatmap', [BestTimesController::class, 'heatmap']);
    Route::post('ai-intelligence/best-times/recalculate', [BestTimesController::class, 'recalculate']);

    // Brand Safety Checks (content-scoped)
    Route::post('contents/{contentId}/safety-check', [BrandSafetyController::class, 'store']);
    Route::get('contents/{contentId}/safety-checks', [BrandSafetyController::class, 'index']);

    // Brand Safety Rules
    Route::post('brand-safety/rules', [BrandSafetyRuleController::class, 'store']);
    Route::get('brand-safety/rules', [BrandSafetyRuleController::class, 'index']);
    Route::put('brand-safety/rules/{ruleId}', [BrandSafetyRuleController::class, 'update']);
    Route::delete('brand-safety/rules/{ruleId}', [BrandSafetyRuleController::class, 'destroy']);

    // Calendar Suggestions
    Route::post('ai-intelligence/calendar/suggest', [CalendarSuggestionController::class, 'suggest']);
    Route::get('ai-intelligence/calendar/suggestions', [CalendarSuggestionController::class, 'index']);
    Route::get('ai-intelligence/calendar/suggestions/{id}', [CalendarSuggestionController::class, 'show']);
    Route::post('ai-intelligence/calendar/suggestions/{id}/accept', [CalendarSuggestionController::class, 'accept']);

    // Content DNA Profiling
    Route::post('ai-intelligence/content-profile/generate', [ContentProfileController::class, 'generate']);
    Route::get('ai-intelligence/content-profile', [ContentProfileController::class, 'show']);
    Route::get('ai-intelligence/content-profile/themes', [ContentProfileController::class, 'themes']);
    Route::post('ai-intelligence/content-profile/recommend', [ContentProfileController::class, 'recommend']);

    // Performance Prediction
    Route::post('contents/{contentId}/predict-performance', [PerformancePredictionController::class, 'store']);
    Route::get('contents/{contentId}/predictions', [PerformancePredictionController::class, 'index']);

    // Audience Insights
    Route::get('ai-intelligence/audience-insights', [AudienceInsightsController::class, 'index']);
    Route::post('ai-intelligence/audience-insights/refresh', [AudienceInsightsController::class, 'refresh']);

    // Content Gap Analysis
    Route::post('ai-intelligence/gap-analysis/generate', [ContentGapAnalysisController::class, 'generate']);
    Route::get('ai-intelligence/gap-analyses', [ContentGapAnalysisController::class, 'index']);
    Route::get('ai-intelligence/gap-analyses/{id}', [ContentGapAnalysisController::class, 'show']);
    Route::get('ai-intelligence/gap-analyses/{id}/opportunities', [ContentGapAnalysisController::class, 'opportunities']);

    // Prediction Accuracy (AI Learning — N4)
    Route::post('ai-intelligence/prediction-validations', [PredictionAccuracyController::class, 'store']);
    Route::get('ai-intelligence/prediction-accuracy', [PredictionAccuracyController::class, 'index']);

    // Style Profile (AI Learning — N5)
    Route::post('ai-intelligence/style-profile', [StyleProfileController::class, 'store']);
});
