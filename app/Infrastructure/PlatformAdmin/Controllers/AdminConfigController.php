<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Controllers;

use App\Application\PlatformAdmin\DTOs\UpdateSystemConfigInput;
use App\Application\PlatformAdmin\UseCases\GetSystemConfigUseCase;
use App\Application\PlatformAdmin\UseCases\UpdateSystemConfigUseCase;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Infrastructure\PlatformAdmin\Requests\UpdateConfigRequest;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminConfigController
{
    public function index(
        Request $request,
        GetSystemConfigUseCase $useCase,
    ): JsonResponse {
        $configs = $useCase->execute();

        return ApiResponse::success($configs);
    }

    public function update(
        UpdateConfigRequest $request,
        UpdateSystemConfigUseCase $useCase,
    ): JsonResponse {
        $configsArray = [];
        foreach ($request->validated('configs') as $entry) {
            $configsArray[$entry['key']] = $entry['value'];
        }

        $useCase->execute(
            new UpdateSystemConfigInput(
                configs: $configsArray,
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }
}
