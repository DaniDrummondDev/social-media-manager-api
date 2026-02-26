<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GetBestTimesInput;
use App\Application\AIIntelligence\DTOs\RecalculateBestTimesInput;
use App\Application\AIIntelligence\UseCases\GetBestTimesHeatmapUseCase;
use App\Application\AIIntelligence\UseCases\GetBestTimesUseCase;
use App\Application\AIIntelligence\UseCases\RecalculateBestTimesUseCase;
use App\Infrastructure\AIIntelligence\Jobs\CalculateBestPostingTimesJob;
use App\Infrastructure\AIIntelligence\Requests\GetBestTimesRequest;
use App\Infrastructure\AIIntelligence\Requests\RecalculateBestTimesRequest;
use App\Infrastructure\AIIntelligence\Resources\BestTimesHeatmapResource;
use App\Infrastructure\AIIntelligence\Resources\BestTimesResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class BestTimesController
{
    public function index(
        GetBestTimesRequest $request,
        GetBestTimesUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetBestTimesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $request->validated('provider'),
            socialAccountId: $request->validated('social_account_id'),
        ));

        if ($output === null) {
            return ApiResponse::success(null);
        }

        return ApiResponse::success(
            BestTimesResource::fromOutput($output)->toArray(),
        );
    }

    public function heatmap(
        GetBestTimesRequest $request,
        GetBestTimesHeatmapUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetBestTimesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $request->validated('provider'),
            socialAccountId: $request->validated('social_account_id'),
        ));

        if ($output === null) {
            return ApiResponse::success(null);
        }

        return ApiResponse::success(
            BestTimesHeatmapResource::fromOutput($output)->toArray(),
        );
    }

    public function recalculate(
        RecalculateBestTimesRequest $request,
        RecalculateBestTimesUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(new RecalculateBestTimesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            provider: $request->validated('provider'),
            socialAccountId: $request->validated('social_account_id'),
        ));

        CalculateBestPostingTimesJob::dispatch(
            $request->attributes->get('auth_organization_id'),
            $request->attributes->get('auth_user_id'),
            $request->validated('provider'),
            $request->validated('social_account_id'),
        );

        return ApiResponse::success([
            'message' => 'Recalculation queued. Results will be available shortly.',
        ], status: 202);
    }
}
