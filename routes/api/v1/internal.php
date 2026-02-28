<?php

declare(strict_types=1);

use App\Infrastructure\Shared\Http\Controllers\AgentCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal API Routes
|--------------------------------------------------------------------------
|
| Routes accessible only from the Docker internal network.
| Protected by the 'internal-only' middleware (X-Internal-Secret + IP check).
|
*/

Route::prefix('internal')->middleware(['internal-only'])->group(function (): void {
    Route::post('/agent-callback', [AgentCallbackController::class, 'handle']);
});
