<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\ListAudienceInsightsInput;
use App\Application\AIIntelligence\DTOs\RefreshAudienceInsightsInput;
use App\Application\AIIntelligence\UseCases\ListAudienceInsightsUseCase;
use App\Application\AIIntelligence\UseCases\RefreshAudienceInsightsUseCase;
use App\Infrastructure\AIIntelligence\Jobs\RefreshAudienceInsightsJob;
use App\Infrastructure\AIIntelligence\Requests\ListAudienceInsightsRequest;
use App\Infrastructure\AIIntelligence\Resources\AudienceInsightResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AudienceInsightsController
{
    public function index(
        ListAudienceInsightsRequest $request,
        ListAudienceInsightsUseCase $useCase,
    ): JsonResponse {
        $input = new ListAudienceInsightsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            type: $request->validated('type'),
            socialAccountId: $request->validated('social_account_id'),
        );

        $insights = $useCase->execute($input);

        return ApiResponse::success(
            array_map(
                fn ($insight) => AudienceInsightResource::fromOutput($insight)->toArray(),
                $insights,
            ),
        );
    }

    public function refresh(
        Request $request,
        RefreshAudienceInsightsUseCase $useCase,
    ): JsonResponse {
        $organizationId = $request->attributes->get('auth_organization_id');
        $userId = $request->attributes->get('auth_user_id');

        $input = new RefreshAudienceInsightsInput(
            organizationId: $organizationId,
            userId: $userId,
        );

        $useCase->execute($input);

        RefreshAudienceInsightsJob::dispatch($organizationId, $userId);

        return ApiResponse::success([
            'message' => 'Audience insights refresh queued.',
            'estimated_completion' => now()->addMinutes(15)->toIso8601String(),
        ], status: 202);
    }
}
