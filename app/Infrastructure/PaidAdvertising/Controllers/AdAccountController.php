<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Controllers;

use App\Application\PaidAdvertising\DTOs\ConnectAdAccountInput;
use App\Application\PaidAdvertising\DTOs\DisconnectAdAccountInput;
use App\Application\PaidAdvertising\DTOs\GetAdAccountStatusInput;
use App\Application\PaidAdvertising\DTOs\HandleAdAccountCallbackInput;
use App\Application\PaidAdvertising\DTOs\ListAdAccountsInput;
use App\Application\PaidAdvertising\DTOs\TestAdAccountConnectionInput;
use App\Application\PaidAdvertising\UseCases\ConnectAdAccountUseCase;
use App\Application\PaidAdvertising\UseCases\DisconnectAdAccountUseCase;
use App\Application\PaidAdvertising\UseCases\GetAdAccountStatusUseCase;
use App\Application\PaidAdvertising\UseCases\HandleAdAccountCallbackUseCase;
use App\Application\PaidAdvertising\UseCases\ListAdAccountsUseCase;
use App\Application\PaidAdvertising\UseCases\TestAdAccountConnectionUseCase;
use App\Infrastructure\PaidAdvertising\Requests\ConnectAdAccountRequest;
use App\Infrastructure\PaidAdvertising\Requests\HandleAdAccountCallbackRequest;
use App\Infrastructure\PaidAdvertising\Resources\AdAccountResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdAccountController
{
    public function connect(
        ConnectAdAccountRequest $request,
        ConnectAdAccountUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new ConnectAdAccountInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            provider: $request->validated('provider'),
            scopes: $request->validated('scopes', []),
        ));

        return ApiResponse::success([
            'authorization_url' => $output->authorizationUrl,
            'state' => $output->state,
        ]);
    }

    public function callback(
        HandleAdAccountCallbackRequest $request,
        HandleAdAccountCallbackUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new HandleAdAccountCallbackInput(
            code: $request->validated('code'),
            state: $request->validated('state'),
        ));

        return ApiResponse::success(
            AdAccountResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        ListAdAccountsUseCase $useCase,
    ): JsonResponse {
        $accounts = $useCase->execute(new ListAdAccountsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $request->query('provider'),
        ));

        $data = array_map(
            fn ($item) => AdAccountResource::fromOutput($item)->toArray(),
            $accounts,
        );

        return ApiResponse::success($data);
    }

    public function show(
        Request $request,
        GetAdAccountStatusUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new GetAdAccountStatusInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            accountId: $id,
        ));

        return ApiResponse::success(
            AdAccountResource::fromOutput($output)->toArray(),
        );
    }

    public function test(
        Request $request,
        TestAdAccountConnectionUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new TestAdAccountConnectionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            accountId: $id,
        ));

        return ApiResponse::success([
            'is_connected' => $output->isConnected,
            'provider_account_name' => $output->providerAccountName,
            'error' => $output->error,
        ]);
    }

    public function destroy(
        Request $request,
        DisconnectAdAccountUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(new DisconnectAdAccountInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            accountId: $id,
        ));

        return ApiResponse::noContent();
    }
}
