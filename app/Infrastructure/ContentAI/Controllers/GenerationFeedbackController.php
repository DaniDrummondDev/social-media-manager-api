<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Controllers;

use App\Application\ContentAI\DTOs\RecordGenerationFeedbackInput;
use App\Application\ContentAI\UseCases\RecordGenerationFeedbackUseCase;
use App\Infrastructure\ContentAI\Requests\RecordGenerationFeedbackRequest;
use App\Infrastructure\ContentAI\Resources\GenerationFeedbackResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class GenerationFeedbackController
{
    public function store(
        RecordGenerationFeedbackRequest $request,
        RecordGenerationFeedbackUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new RecordGenerationFeedbackInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            generationId: $request->validated('generation_id'),
            action: $request->validated('action'),
            originalOutput: $request->validated('original_output'),
            editedOutput: $request->validated('edited_output'),
            contentId: $request->validated('content_id'),
            generationType: $request->validated('generation_type'),
            timeToDecisionMs: $request->validated('time_to_decision_ms'),
        ));

        return ApiResponse::success(
            GenerationFeedbackResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }
}
