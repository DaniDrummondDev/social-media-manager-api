<?php

declare(strict_types=1);

use App\Infrastructure\PlatformAdmin\Controllers\AdminAuditController;
use App\Infrastructure\PlatformAdmin\Controllers\AdminConfigController;
use App\Infrastructure\PlatformAdmin\Controllers\AdminDashboardController;
use App\Infrastructure\PlatformAdmin\Controllers\AdminOrganizationController;
use App\Infrastructure\PlatformAdmin\Controllers\AdminPlanController;
use App\Infrastructure\PlatformAdmin\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

// SECURITY FIX (ADMIN-001): Add IP whitelist middleware to admin routes
Route::middleware(['auth.jwt', 'ip.whitelist', 'admin'])->prefix('admin')->group(function () {
    // Dashboard — all admin roles
    Route::get('dashboard', [AdminDashboardController::class, 'dashboard']);

    // Organizations
    Route::get('organizations', [AdminOrganizationController::class, 'index']);
    Route::get('organizations/{id}', [AdminOrganizationController::class, 'show']);
    Route::middleware('admin:admin')->group(function () {
        Route::post('organizations/{id}/suspend', [AdminOrganizationController::class, 'suspend']);
        Route::post('organizations/{id}/unsuspend', [AdminOrganizationController::class, 'unsuspend']);
    });
    Route::middleware('admin:super_admin')->delete('organizations/{id}', [AdminOrganizationController::class, 'destroy']);

    // Users
    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::post('users/{id}/force-verify', [AdminUserController::class, 'forceVerify']);
    Route::post('users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
    Route::middleware('admin:admin')->group(function () {
        Route::post('users/{id}/ban', [AdminUserController::class, 'ban']);
        Route::post('users/{id}/unban', [AdminUserController::class, 'unban']);
    });

    // Plans
    Route::middleware('admin:admin')->group(function () {
        Route::get('plans', [AdminPlanController::class, 'index']);
        Route::get('plans/{id}/subscribers', [AdminPlanController::class, 'subscribers']);
    });
    Route::middleware('admin:super_admin')->group(function () {
        Route::post('plans', [AdminPlanController::class, 'store']);
        Route::patch('plans/{id}', [AdminPlanController::class, 'update']);
        Route::post('plans/{id}/deactivate', [AdminPlanController::class, 'deactivate']);
    });

    // System Config
    Route::middleware('admin:admin')->get('config', [AdminConfigController::class, 'index']);
    Route::middleware('admin:super_admin')->patch('config', [AdminConfigController::class, 'update']);

    // Audit Log
    Route::middleware('admin:admin')->get('audit-log', [AdminAuditController::class, 'index']);
});
