<?php

use App\Infrastructure\Engagement\Controllers\CrmConnectionController;
use App\Infrastructure\Engagement\Controllers\CrmFieldMappingController;
use App\Infrastructure\Engagement\Controllers\CrmSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context', 'tenant.rls'])->group(function () {
    // CRM Connections
    Route::post('crm/connect', [CrmConnectionController::class, 'connect']);
    Route::post('crm/callback', [CrmConnectionController::class, 'callback']);
    Route::get('crm/connections', [CrmConnectionController::class, 'index']);
    Route::get('crm/connections/{id}', [CrmConnectionController::class, 'show']);
    Route::post('crm/connections/{id}/test', [CrmConnectionController::class, 'test']);
    Route::delete('crm/connections/{id}', [CrmConnectionController::class, 'destroy']);

    // CRM Field Mappings
    Route::get('crm/connections/{connectionId}/mappings', [CrmFieldMappingController::class, 'index']);
    Route::put('crm/connections/{connectionId}/mappings', [CrmFieldMappingController::class, 'update']);
    Route::post('crm/connections/{connectionId}/mappings/reset', [CrmFieldMappingController::class, 'reset']);

    // CRM Sync Logs
    Route::get('crm/connections/{connectionId}/logs', [CrmSyncController::class, 'logs']);
});
