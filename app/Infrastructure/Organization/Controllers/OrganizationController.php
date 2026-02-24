<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Controllers;

use App\Application\Organization\DTOs\CreateOrganizationInput;
use App\Application\Organization\DTOs\SwitchOrganizationInput;
use App\Application\Organization\DTOs\UpdateOrganizationInput;
use App\Application\Organization\UseCases\CreateOrganizationUseCase;
use App\Application\Organization\UseCases\ListOrganizationsUseCase;
use App\Application\Organization\UseCases\SwitchOrganizationUseCase;
use App\Application\Organization\UseCases\UpdateOrganizationUseCase;
use App\Infrastructure\Identity\Resources\AuthTokensResource;
use App\Infrastructure\Organization\Requests\CreateOrganizationRequest;
use App\Infrastructure\Organization\Requests\UpdateOrganizationRequest;
use App\Infrastructure\Organization\Resources\OrganizationResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrganizationController
{
    public function create(CreateOrganizationRequest $request, CreateOrganizationUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new CreateOrganizationInput(
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            timezone: $request->validated('timezone', 'America/Sao_Paulo'),
        ));

        return ApiResponse::success(OrganizationResource::fromOutput($output)->toArray(), status: 201);
    }

    public function list(Request $request, ListOrganizationsUseCase $useCase): JsonResponse
    {
        $userId = $request->attributes->get('auth_user_id');

        $output = $useCase->execute($userId);

        $data = array_map(
            fn ($org) => OrganizationResource::fromOutput($org)->toArray(),
            $output->organizations,
        );

        return ApiResponse::success($data);
    }

    public function update(
        UpdateOrganizationRequest $request,
        UpdateOrganizationUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateOrganizationInput(
            organizationId: $id,
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            timezone: $request->validated('timezone'),
        ));

        return ApiResponse::success(OrganizationResource::fromOutput($output)->toArray());
    }

    public function switchOrganization(Request $request, SwitchOrganizationUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new SwitchOrganizationInput(
            userId: $request->attributes->get('auth_user_id'),
            organizationId: $request->input('organization_id'),
        ));

        return ApiResponse::success(AuthTokensResource::fromOutput($output)->toArray());
    }
}
