<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GetPredictionAccuracyInput;
use App\Application\AIIntelligence\DTOs\ValidatePredictionInput;
use App\Application\AIIntelligence\UseCases\GetPredictionAccuracyUseCase;
use App\Application\AIIntelligence\UseCases\ValidatePredictionUseCase;
use App\Infrastructure\AIIntelligence\Requests\ValidatePredictionRequest;
use App\Infrastructure\AIIntelligence\Resources\PredictionAccuracyResource;
use App\Infrastructure\AIIntelligence\Resources\PredictionValidationResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PredictionAccuracyController
{
    public function store(
        ValidatePredictionRequest $request,
        ValidatePredictionUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new ValidatePredictionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            predictionId: $request->validated('prediction_id'),
            contentId: $request->validated('content_id'),
            provider: $request->validated('provider'),
            actualEngagementRate: (float) $request->validated('actual_engagement_rate'),
            metricsSnapshot: $request->validated('metrics_snapshot'),
            metricsCapturedAt: $request->validated('metrics_captured_at'),
        ));

        return ApiResponse::success(
            PredictionValidationResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        GetPredictionAccuracyUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetPredictionAccuracyInput(
            organizationId: $request->attributes->get('auth_organization_id'),
        ));

        return ApiResponse::success(
            PredictionAccuracyResource::fromOutput($output)->toArray(),
        );
    }
}
