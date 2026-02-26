<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Controllers;

use App\Application\ContentAI\DTOs\CreatePromptTemplateInput;
use App\Application\ContentAI\UseCases\CreatePromptTemplateUseCase;
use App\Infrastructure\ContentAI\Requests\CreatePromptTemplateRequest;
use App\Infrastructure\ContentAI\Resources\PromptTemplateResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PromptTemplateController
{
    public function store(
        CreatePromptTemplateRequest $request,
        CreatePromptTemplateUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreatePromptTemplateInput(
            userId: $request->attributes->get('auth_user_id'),
            generationType: $request->validated('generation_type'),
            version: $request->validated('version'),
            name: $request->validated('name'),
            systemPrompt: $request->validated('system_prompt'),
            userPromptTemplate: $request->validated('user_prompt_template'),
            variables: $request->validated('variables', []),
            isDefault: (bool) $request->validated('is_default', false),
            organizationId: $request->attributes->get('auth_organization_id'),
        ));

        return ApiResponse::success(
            PromptTemplateResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }
}
