<?php

use App\Infrastructure\SocialListening\Controllers\ListeningAlertController;
use App\Infrastructure\SocialListening\Controllers\ListeningDashboardController;
use App\Infrastructure\SocialListening\Controllers\ListeningQueryController;
use App\Infrastructure\SocialListening\Controllers\ListeningReportController;
use App\Infrastructure\SocialListening\Controllers\MentionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->group(function () {
    // Listening Queries
    Route::post('listening/queries', [ListeningQueryController::class, 'store']);
    Route::get('listening/queries', [ListeningQueryController::class, 'index']);
    Route::patch('listening/queries/{queryId}', [ListeningQueryController::class, 'update']);
    Route::post('listening/queries/{queryId}/pause', [ListeningQueryController::class, 'pause']);
    Route::post('listening/queries/{queryId}/resume', [ListeningQueryController::class, 'resume']);
    Route::delete('listening/queries/{queryId}', [ListeningQueryController::class, 'destroy']);

    // Mentions
    Route::get('mentions', [MentionController::class, 'index']);
    Route::get('mentions/{mentionId}', [MentionController::class, 'show']);
    Route::post('mentions/{mentionId}/flag', [MentionController::class, 'flag']);
    Route::post('mentions/{mentionId}/unflag', [MentionController::class, 'unflag']);
    Route::post('mentions/mark-read', [MentionController::class, 'markRead']);

    // Listening Alerts
    Route::post('listening/alerts', [ListeningAlertController::class, 'store']);
    Route::get('listening/alerts', [ListeningAlertController::class, 'index']);
    Route::patch('listening/alerts/{alertId}', [ListeningAlertController::class, 'update']);
    Route::delete('listening/alerts/{alertId}', [ListeningAlertController::class, 'destroy']);

    // Listening Dashboard
    Route::get('listening/dashboard', [ListeningDashboardController::class, 'index']);
    Route::get('listening/dashboard/sentiment-trend', [ListeningDashboardController::class, 'sentimentTrend']);
    Route::get('listening/dashboard/platform-breakdown', [ListeningDashboardController::class, 'platformBreakdown']);

    // Listening Reports
    Route::post('listening/reports', [ListeningReportController::class, 'store']);
    Route::get('listening/reports', [ListeningReportController::class, 'index']);
    Route::get('listening/reports/{reportId}', [ListeningReportController::class, 'show']);
});
