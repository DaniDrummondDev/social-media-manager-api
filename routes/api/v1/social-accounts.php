<?php

use App\Infrastructure\SocialAccount\Controllers\SocialAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->group(function () {
    Route::get('social-accounts/oauth/callback', [SocialAccountController::class, 'handleCallback']);
    Route::get('social-accounts/oauth/{provider}', [SocialAccountController::class, 'initiateOAuth']);
    Route::get('social-accounts', [SocialAccountController::class, 'list']);
    Route::delete('social-accounts/{id}', [SocialAccountController::class, 'disconnect']);
    Route::post('social-accounts/{id}/refresh', [SocialAccountController::class, 'refreshToken']);
    Route::get('social-accounts/{id}/health', [SocialAccountController::class, 'checkHealth']);
});
