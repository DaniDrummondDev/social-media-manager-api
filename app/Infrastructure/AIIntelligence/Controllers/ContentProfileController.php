<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GenerateContentProfileInput;
use App\Application\AIIntelligence\DTOs\GetContentProfileInput;
use App\Application\AIIntelligence\DTOs\GetContentRecommendationsInput;
use App\Application\AIIntelligence\DTOs\GetContentThemesInput;
use App\Application\AIIntelligence\UseCases\GenerateContentProfileUseCase;
use App\Application\AIIntelligence\UseCases\GetContentProfileUseCase;
use App\Application\AIIntelligence\UseCases\GetContentRecommendationsUseCase;
use App\Application\AIIntelligence\UseCases\GetContentThemesUseCase;
use App\Infrastructure\AIIntelligence\Jobs\GenerateContentProfileJob;
use App\Infrastructure\AIIntelligence\Requests\GenerateContentProfileRequest;
use App\Infrastructure\AIIntelligence\Requests\GetContentProfileRequest;
use App\Infrastructure\AIIntelligence\Requests\GetContentRecommendationsRequest;
use App\Infrastructure\AIIntelligence\Requests\GetContentThemesRequest;
use App\Infrastructure\AIIntelligence\Resources\ContentProfileResource;
use App\Infrastructure\AIIntelligence\Resources\ContentThemesResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ContentProfileController
{
    public function generate(
        GenerateContentProfileRequest $request,
        GenerateContentProfileUseCase $useCase,
    ): JsonResponse {
        $input = new GenerateContentProfileInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $request->validated('provider'),
            socialAccountId: $request->validated('social_account_id'),
            userId: $request->attributes->get('auth_user_id'),
        );

        $output = $useCase->execute($input);

        GenerateContentProfileJob::dispatch(
            profileId: $output->profileId,
            organizationId: $input->organizationId,
            userId: $input->userId,
        );

        return ApiResponse::success([
            'profile_id' => $output->profileId,
            'status' => $output->status,
            'message' => $output->message,
        ], status: 202);
    }

    public function show(
        GetContentProfileRequest $request,
        GetContentProfileUseCase $useCase,
    ): JsonResponse {
        $input = new GetContentProfileInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $request->validated('provider'),
            socialAccountId: $request->validated('social_account_id'),
        );

        $output = $useCase->execute($input);

        return ApiResponse::success(
            ContentProfileResource::fromOutput($output)->toArray(),
        );
    }

    public function themes(
        GetContentThemesRequest $request,
        GetContentThemesUseCase $useCase,
    ): JsonResponse {
        $input = new GetContentThemesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $request->validated('provider'),
            socialAccountId: $request->validated('social_account_id'),
            limit: (int) ($request->validated('limit') ?? 10),
        );

        $output = $useCase->execute($input);

        return ApiResponse::success(
            ContentThemesResource::fromOutput($output)->toArray(),
        );
    }

    public function recommend(
        GetContentRecommendationsRequest $request,
        GetContentRecommendationsUseCase $useCase,
    ): JsonResponse {
        $input = new GetContentRecommendationsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            topic: $request->validated('topic'),
            limit: (int) ($request->validated('limit') ?? 5),
            provider: $request->validated('provider'),
        );

        $output = $useCase->execute($input);

        return ApiResponse::success([
            'recommendations' => $output->recommendations,
        ]);
    }
}
