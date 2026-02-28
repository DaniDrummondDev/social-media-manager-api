<?php

declare(strict_types=1);

use App\Infrastructure\Identity\Controllers\AuthController;
use App\Infrastructure\Identity\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public auth routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth.register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth.login');
    Route::post('2fa/verify', [AuthController::class, 'verify2fa'])->middleware('throttle:auth.2fa');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:auth.refresh');
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:auth.password');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth.password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth.password');
});

// Authenticated auth routes
Route::middleware('auth.jwt')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('2fa/enable', [AuthController::class, 'enable2fa']);
        Route::post('2fa/confirm', [AuthController::class, 'confirm2fa']);
        Route::post('2fa/disable', [AuthController::class, 'disable2fa']);
    });

    // Profile routes
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::put('profile/email', [ProfileController::class, 'changeEmail']);
    Route::put('profile/password', [ProfileController::class, 'changePassword']);
});
