<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GetAdPerformanceInsightsInput;
use App\Application\AIIntelligence\DTOs\GetAdTargetingSuggestionsInput;
use App\Application\AIIntelligence\UseCases\GetAdPerformanceInsightsUseCase;
use App\Application\AIIntelligence\UseCases\GetAdTargetingSuggestionsUseCase;
use App\Infrastructure\AIIntelligence\Requests\ListAdPerformanceInsightsRequest;
use App\Infrastructure\AIIntelligence\Resources\AdPerformanceInsightResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdIntelligenceController
{
    public function insights(
        ListAdPerformanceInsightsRequest $request,
        GetAdPerformanceInsightsUseCase $useCase,
    ): JsonResponse {
        $input = new GetAdPerformanceInsightsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            adInsightType: $request->validated('type'),
        );

        $insights = $useCase->execute($input);

        return ApiResponse::success(
            array_map(
                fn ($insight) => AdPerformanceInsightResource::fromOutput($insight)->toArray(),
                $insights,
            ),
        );
    }

    public function targetingSuggestions(
        Request $request,
        GetAdTargetingSuggestionsUseCase $useCase,
    ): JsonResponse {
        $input = new GetAdTargetingSuggestionsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            contentId: $request->query('content_id', ''),
        );

        $output = $useCase->execute($input);

        return ApiResponse::success([
            'suggestions' => $output->suggestions,
            'suggestion_count' => $output->suggestionCount,
            'based_on_insight_type' => $output->basedOnInsightType,
            'confidence_level' => $output->confidenceLevel,
        ]);
    }
}
