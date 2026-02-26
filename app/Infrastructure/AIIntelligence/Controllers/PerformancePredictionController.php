<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GetPredictionsInput;
use App\Application\AIIntelligence\DTOs\PredictPerformanceInput;
use App\Application\AIIntelligence\DTOs\PredictionOutput;
use App\Application\AIIntelligence\DTOs\PredictionSummaryOutput;
use App\Application\AIIntelligence\UseCases\GetPredictionsUseCase;
use App\Application\AIIntelligence\UseCases\PredictPerformanceUseCase;
use App\Infrastructure\AIIntelligence\Requests\PredictPerformanceRequest;
use App\Infrastructure\AIIntelligence\Resources\PredictionResource;
use App\Infrastructure\AIIntelligence\Resources\PredictionSummaryResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PerformancePredictionController
{
    public function store(
        PredictPerformanceRequest $request,
        string $contentId,
        PredictPerformanceUseCase $useCase,
    ): JsonResponse {
        $input = new PredictPerformanceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            contentId: $contentId,
            providers: $request->validated('providers'),
            detailed: (bool) ($request->validated('detailed') ?? false),
            userId: $request->attributes->get('auth_user_id'),
        );

        $outputs = $useCase->execute($input);

        return ApiResponse::success(
            array_map(
                fn (PredictionOutput $o) => PredictionResource::fromOutput($o)->toArray(),
                $outputs,
            ),
        );
    }

    public function index(
        Request $request,
        string $contentId,
        GetPredictionsUseCase $useCase,
    ): JsonResponse {
        $input = new GetPredictionsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            contentId: $contentId,
        );

        $outputs = $useCase->execute($input);

        return ApiResponse::success(
            array_map(
                fn (PredictionSummaryOutput $o) => PredictionSummaryResource::fromOutput($o)->toArray(),
                $outputs,
            ),
        );
    }
}
