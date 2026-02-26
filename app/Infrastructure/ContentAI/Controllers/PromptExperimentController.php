<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Controllers;

use App\Application\ContentAI\DTOs\CreatePromptExperimentInput;
use App\Application\ContentAI\DTOs\EvaluateExperimentInput;
use App\Application\ContentAI\UseCases\CreatePromptExperimentUseCase;
use App\Application\ContentAI\UseCases\EvaluateExperimentUseCase;
use App\Infrastructure\ContentAI\Requests\CreatePromptExperimentRequest;
use App\Infrastructure\ContentAI\Resources\PromptExperimentResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PromptExperimentController
{
    public function store(
        CreatePromptExperimentRequest $request,
        CreatePromptExperimentUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreatePromptExperimentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            generationType: $request->validated('generation_type'),
            name: $request->validated('name'),
            variantAId: $request->validated('variant_a_id'),
            variantBId: $request->validated('variant_b_id'),
            trafficSplit: (float) $request->validated('traffic_split', 0.5),
            minSampleSize: (int) $request->validated('min_sample_size', 50),
        ));

        return ApiResponse::success(
            PromptExperimentResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function evaluate(
        Request $request,
        string $experimentId,
        EvaluateExperimentUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new EvaluateExperimentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            experimentId: $experimentId,
        ));

        return ApiResponse::success(
            PromptExperimentResource::fromOutput($output)->toArray(),
        );
    }
}
