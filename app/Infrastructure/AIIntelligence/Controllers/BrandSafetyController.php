<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GetSafetyChecksInput;
use App\Application\AIIntelligence\DTOs\RunSafetyCheckInput;
use App\Application\AIIntelligence\UseCases\GetSafetyChecksUseCase;
use App\Application\AIIntelligence\UseCases\RunSafetyCheckUseCase;
use App\Infrastructure\AIIntelligence\Jobs\RunBrandSafetyCheckJob;
use App\Infrastructure\AIIntelligence\Requests\RunSafetyCheckRequest;
use App\Infrastructure\AIIntelligence\Resources\SafetyCheckResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BrandSafetyController
{
    public function store(
        RunSafetyCheckRequest $request,
        string $contentId,
        RunSafetyCheckUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new RunSafetyCheckInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            contentId: $contentId,
            providers: $request->validated('providers'),
        ));

        RunBrandSafetyCheckJob::dispatch(
            $output->checkId,
            $request->attributes->get('auth_organization_id'),
            $contentId,
        );

        return ApiResponse::success([
            'check_id' => $output->checkId,
            'content_id' => $output->contentId,
            'status' => $output->status,
            'message' => $output->message,
        ], status: 202);
    }

    public function index(
        Request $request,
        string $contentId,
        GetSafetyChecksUseCase $useCase,
    ): JsonResponse {
        $checks = $useCase->execute(new GetSafetyChecksInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            contentId: $contentId,
        ));

        return ApiResponse::success(
            array_map(fn ($check) => SafetyCheckResource::fromOutput($check)->toArray(), $checks),
        );
    }
}
