<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controllers;

use App\Application\Billing\UseCases\ListPlansUseCase;
use App\Infrastructure\Billing\Resources\PlanResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PlanController
{
    public function index(ListPlansUseCase $useCase): JsonResponse
    {
        $plans = $useCase->execute();

        return ApiResponse::success(
            array_map(fn ($p) => PlanResource::fromOutput($p)->toArray(), $plans),
        );
    }
}
