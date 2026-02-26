<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Controllers;

use App\Application\Engagement\DTOs\ConnectCrmInput;
use App\Application\Engagement\DTOs\HandleCrmCallbackInput;
use App\Application\Engagement\UseCases\ConnectCrmUseCase;
use App\Application\Engagement\UseCases\DisconnectCrmUseCase;
use App\Application\Engagement\UseCases\GetCrmConnectionStatusUseCase;
use App\Application\Engagement\UseCases\HandleCrmCallbackUseCase;
use App\Application\Engagement\UseCases\ListCrmConnectionsUseCase;
use App\Application\Engagement\UseCases\TestCrmConnectionUseCase;
use App\Infrastructure\Engagement\Requests\ConnectCrmRequest;
use App\Infrastructure\Engagement\Requests\HandleCrmCallbackRequest;
use App\Infrastructure\Engagement\Resources\CrmConnectionResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CrmConnectionController
{
    public function connect(
        ConnectCrmRequest $request,
        ConnectCrmUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new ConnectCrmInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            provider: $request->validated('provider'),
        ));

        return ApiResponse::success([
            'authorization_url' => $output->authorizationUrl,
            'state' => $output->state,
        ]);
    }

    public function callback(
        HandleCrmCallbackRequest $request,
        HandleCrmCallbackUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new HandleCrmCallbackInput(
            code: $request->validated('code'),
            state: $request->validated('state'),
        ));

        return ApiResponse::success(
            CrmConnectionResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        ListCrmConnectionsUseCase $useCase,
    ): JsonResponse {
        $connections = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
        );

        $data = array_map(
            fn ($item) => CrmConnectionResource::fromOutput($item)->toArray(),
            $connections,
        );

        return ApiResponse::success($data);
    }

    public function show(
        Request $request,
        GetCrmConnectionStatusUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $id,
        );

        return ApiResponse::success(
            CrmConnectionResource::fromOutput($output)->toArray(),
        );
    }

    public function test(
        Request $request,
        TestCrmConnectionUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $id,
        );

        return ApiResponse::success(
            CrmConnectionResource::fromOutput($output)->toArray(),
        );
    }

    public function destroy(
        Request $request,
        DisconnectCrmUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $request->attributes->get('auth_user_id'),
            $id,
        );

        return ApiResponse::noContent();
    }
}
