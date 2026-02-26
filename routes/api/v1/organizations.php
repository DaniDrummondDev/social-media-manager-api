<?php

declare(strict_types=1);

use App\Infrastructure\Organization\Controllers\MemberController;
use App\Infrastructure\Organization\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

// Public invite acceptance
Route::post('invites/accept', [MemberController::class, 'acceptInvite'])->middleware('auth.jwt');

// Authenticated organization routes
Route::middleware('auth.jwt')->group(function () {
    Route::get('organizations', [OrganizationController::class, 'list']);
    Route::post('organizations', [OrganizationController::class, 'create']);
    Route::post('organizations/switch', [OrganizationController::class, 'switchOrganization']);

    // Routes requiring organization context
    Route::middleware(['org.context', 'tenant.rls'])->group(function () {
        Route::put('organizations/{id}', [OrganizationController::class, 'update']);
        Route::get('organizations/{organizationId}/members', [MemberController::class, 'list']);
        Route::post('organizations/{organizationId}/members/invite', [MemberController::class, 'invite']);
        Route::delete('organizations/{organizationId}/members/{userId}', [MemberController::class, 'remove']);
        Route::put('organizations/{organizationId}/members/{userId}/role', [MemberController::class, 'changeRole']);
    });
});
