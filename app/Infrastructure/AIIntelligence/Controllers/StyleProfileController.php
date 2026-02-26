<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GenerateStyleProfileInput;
use App\Application\AIIntelligence\UseCases\GenerateStyleProfileUseCase;
use App\Infrastructure\AIIntelligence\Requests\GenerateStyleProfileRequest;
use App\Infrastructure\AIIntelligence\Resources\StyleProfileResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class StyleProfileController
{
    public function store(
        GenerateStyleProfileRequest $request,
        GenerateStyleProfileUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GenerateStyleProfileInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            generationType: $request->validated('generation_type'),
        ));

        return ApiResponse::success(
            StyleProfileResource::fromOutput($output)->toArray(),
        );
    }
}
