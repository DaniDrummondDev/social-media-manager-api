<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Controllers;

use App\Application\ContentAI\DTOs\AdaptContentInput;
use App\Application\ContentAI\UseCases\AdaptContentUseCase;
use App\Infrastructure\ContentAI\Requests\AdaptContentRequest;
use App\Infrastructure\ContentAI\Resources\AIGenerationResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ContentAdaptationController
{
    public function adaptContent(
        AdaptContentRequest $request,
        AdaptContentUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new AdaptContentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            contentId: $request->validated('content_id'),
            sourceNetwork: $request->validated('source_network'),
            targetNetworks: $request->validated('target_networks'),
            preserveTone: (bool) ($request->validated('preserve_tone') ?? true),
        ));

        return ApiResponse::success(AIGenerationResource::fromOutput($output)->toArray());
    }
}
