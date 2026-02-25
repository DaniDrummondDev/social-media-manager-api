<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Controllers;

use App\Application\PlatformAdmin\DTOs\DeleteOrganizationInput;
use App\Application\PlatformAdmin\DTOs\ListOrganizationsAdminInput;
use App\Application\PlatformAdmin\DTOs\SuspendOrganizationInput;
use App\Application\PlatformAdmin\DTOs\UnsuspendOrganizationInput;
use App\Application\PlatformAdmin\UseCases\DeleteOrganizationUseCase;
use App\Application\PlatformAdmin\UseCases\GetOrganizationDetailUseCase;
use App\Application\PlatformAdmin\UseCases\ListOrganizationsAdminUseCase;
use App\Application\PlatformAdmin\UseCases\SuspendOrganizationUseCase;
use App\Application\PlatformAdmin\UseCases\UnsuspendOrganizationUseCase;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Infrastructure\PlatformAdmin\Requests\DeleteOrganizationRequest;
use App\Infrastructure\PlatformAdmin\Requests\ListOrganizationsRequest;
use App\Infrastructure\PlatformAdmin\Requests\SuspendOrganizationRequest;
use App\Infrastructure\PlatformAdmin\Resources\AdminOrganizationDetailResource;
use App\Infrastructure\PlatformAdmin\Resources\AdminOrganizationResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminOrganizationController
{
    public function index(
        ListOrganizationsRequest $request,
        ListOrganizationsAdminUseCase $useCase,
    ): JsonResponse {
        $input = new ListOrganizationsAdminInput(
            status: $request->validated('status'),
            plan: $request->validated('plan'),
            search: $request->validated('search'),
            from: $request->validated('from'),
            to: $request->validated('to'),
            sort: $request->validated('sort', '-created_at'),
            perPage: (int) $request->validated('per_page', 20),
            cursor: $request->validated('cursor'),
        );

        $result = $useCase->execute($input);

        return ApiResponse::success(
            array_map(fn ($item) => AdminOrganizationResource::fromOutput($item)->toArray(), $result['items']),
            ['per_page' => $input->perPage, 'has_more' => $result['has_more'], 'next_cursor' => $result['next_cursor']],
        );
    }

    public function show(
        string $id,
        Request $request,
        GetOrganizationDetailUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute($id);

        return ApiResponse::success(
            AdminOrganizationDetailResource::fromOutput($output)->toArray(),
        );
    }

    public function suspend(
        string $id,
        SuspendOrganizationRequest $request,
        SuspendOrganizationUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new SuspendOrganizationInput(
                organizationId: $id,
                reason: $request->validated('reason'),
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }

    public function unsuspend(
        string $id,
        Request $request,
        UnsuspendOrganizationUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new UnsuspendOrganizationInput(
                organizationId: $id,
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }

    public function destroy(
        string $id,
        DeleteOrganizationRequest $request,
        DeleteOrganizationUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new DeleteOrganizationInput(
                organizationId: $id,
                reason: $request->validated('reason'),
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }
}
