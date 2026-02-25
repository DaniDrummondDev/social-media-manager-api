<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Controllers;

use App\Application\PlatformAdmin\UseCases\GetDashboardUseCase;
use App\Infrastructure\PlatformAdmin\Resources\DashboardResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminDashboardController
{
    public function dashboard(
        Request $request,
        GetDashboardUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute();

        return ApiResponse::success(
            DashboardResource::fromOutput($output)->toArray(),
        );
    }
}
