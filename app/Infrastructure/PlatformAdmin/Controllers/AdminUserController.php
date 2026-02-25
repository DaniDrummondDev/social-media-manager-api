<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Controllers;

use App\Application\PlatformAdmin\DTOs\BanUserInput;
use App\Application\PlatformAdmin\DTOs\ForceVerifyInput;
use App\Application\PlatformAdmin\DTOs\ListUsersAdminInput;
use App\Application\PlatformAdmin\DTOs\ResetPasswordInput;
use App\Application\PlatformAdmin\DTOs\UnbanUserInput;
use App\Application\PlatformAdmin\UseCases\BanUserUseCase;
use App\Application\PlatformAdmin\UseCases\ForceVerifyUserUseCase;
use App\Application\PlatformAdmin\UseCases\GetUserDetailUseCase;
use App\Application\PlatformAdmin\UseCases\ListUsersAdminUseCase;
use App\Application\PlatformAdmin\UseCases\ResetPasswordUseCase;
use App\Application\PlatformAdmin\UseCases\UnbanUserUseCase;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Infrastructure\PlatformAdmin\Requests\BanUserRequest;
use App\Infrastructure\PlatformAdmin\Requests\ListUsersRequest;
use App\Infrastructure\PlatformAdmin\Resources\AdminUserDetailResource;
use App\Infrastructure\PlatformAdmin\Resources\AdminUserResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminUserController
{
    public function index(
        ListUsersRequest $request,
        ListUsersAdminUseCase $useCase,
    ): JsonResponse {
        $input = new ListUsersAdminInput(
            status: $request->validated('status'),
            search: $request->validated('search'),
            emailVerified: $request->has('email_verified') ? (bool) $request->validated('email_verified') : null,
            twoFactor: $request->has('two_factor') ? (bool) $request->validated('two_factor') : null,
            from: $request->validated('from'),
            to: $request->validated('to'),
            sort: $request->validated('sort', '-created_at'),
            perPage: (int) $request->validated('per_page', 20),
            cursor: $request->validated('cursor'),
        );

        $result = $useCase->execute($input);

        return ApiResponse::success(
            array_map(fn ($item) => AdminUserResource::fromOutput($item)->toArray(), $result['items']),
            ['per_page' => $input->perPage, 'has_more' => $result['has_more'], 'next_cursor' => $result['next_cursor']],
        );
    }

    public function show(
        string $id,
        Request $request,
        GetUserDetailUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute($id);

        return ApiResponse::success(
            AdminUserDetailResource::fromOutput($output)->toArray(),
        );
    }

    public function ban(
        string $id,
        BanUserRequest $request,
        BanUserUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new BanUserInput(
                userId: $id,
                reason: $request->validated('reason'),
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }

    public function unban(
        string $id,
        Request $request,
        UnbanUserUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new UnbanUserInput(
                userId: $id,
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }

    public function forceVerify(
        string $id,
        Request $request,
        ForceVerifyUserUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new ForceVerifyInput(
                userId: $id,
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }

    public function resetPassword(
        string $id,
        Request $request,
        ResetPasswordUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new ResetPasswordInput(
                userId: $id,
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }
}
