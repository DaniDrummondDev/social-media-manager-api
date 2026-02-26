<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Controllers;

use App\Application\Engagement\DTOs\UpdateCrmFieldMappingsInput;
use App\Application\Engagement\UseCases\GetCrmFieldMappingsUseCase;
use App\Application\Engagement\UseCases\ResetCrmFieldMappingsToDefaultUseCase;
use App\Application\Engagement\UseCases\UpdateCrmFieldMappingsUseCase;
use App\Infrastructure\Engagement\Requests\UpdateCrmFieldMappingsRequest;
use App\Infrastructure\Engagement\Resources\CrmFieldMappingResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CrmFieldMappingController
{
    public function index(
        Request $request,
        GetCrmFieldMappingsUseCase $useCase,
        string $connectionId,
    ): JsonResponse {
        $mappings = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $connectionId,
        );

        $data = array_map(
            fn ($item) => CrmFieldMappingResource::fromOutput($item)->toArray(),
            $mappings,
        );

        return ApiResponse::success($data);
    }

    public function update(
        UpdateCrmFieldMappingsRequest $request,
        UpdateCrmFieldMappingsUseCase $useCase,
        string $connectionId,
    ): JsonResponse {
        $mappings = $useCase->execute(new UpdateCrmFieldMappingsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            connectionId: $connectionId,
            mappings: $request->validated('mappings'),
        ));

        $data = array_map(
            fn ($item) => CrmFieldMappingResource::fromOutput($item)->toArray(),
            $mappings,
        );

        return ApiResponse::success($data);
    }

    public function reset(
        Request $request,
        ResetCrmFieldMappingsToDefaultUseCase $useCase,
        string $connectionId,
    ): JsonResponse {
        $mappings = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $request->attributes->get('auth_user_id'),
            $connectionId,
        );

        $data = array_map(
            fn ($item) => CrmFieldMappingResource::fromOutput($item)->toArray(),
            $mappings,
        );

        return ApiResponse::success($data);
    }
}
