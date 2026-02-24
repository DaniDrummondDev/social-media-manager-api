<?php

declare(strict_types=1);

use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $checks = [];
    $healthy = true;

    // Database check
    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable) {
        $checks['database'] = 'error';
        $healthy = false;
    }

    // Redis check
    try {
        Redis::connection()->ping();
        $checks['redis'] = 'ok';
    } catch (\Throwable) {
        $checks['redis'] = 'error';
        $healthy = false;
    }

    $status = $healthy ? 200 : 503;

    return ApiResponse::success([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], status: $status);
})->name('health');
