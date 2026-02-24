<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Controllers;

use App\Application\SocialAccount\DTOs\CheckAccountHealthInput;
use App\Application\SocialAccount\DTOs\DisconnectSocialAccountInput;
use App\Application\SocialAccount\DTOs\HandleOAuthCallbackInput;
use App\Application\SocialAccount\DTOs\InitiateOAuthInput;
use App\Application\SocialAccount\DTOs\RefreshSocialTokenInput;
use App\Application\SocialAccount\UseCases\CheckAccountHealthUseCase;
use App\Application\SocialAccount\UseCases\DisconnectSocialAccountUseCase;
use App\Application\SocialAccount\UseCases\HandleOAuthCallbackUseCase;
use App\Application\SocialAccount\UseCases\InitiateOAuthUseCase;
use App\Application\SocialAccount\UseCases\ListSocialAccountsUseCase;
use App\Application\SocialAccount\UseCases\RefreshSocialTokenUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use App\Infrastructure\SocialAccount\Requests\HandleOAuthCallbackRequest;
use App\Infrastructure\SocialAccount\Requests\InitiateOAuthRequest;
use App\Infrastructure\SocialAccount\Resources\AccountHealthResource;
use App\Infrastructure\SocialAccount\Resources\OAuthInitResource;
use App\Infrastructure\SocialAccount\Resources\SocialAccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SocialAccountController
{
    public function initiateOAuth(
        InitiateOAuthRequest $request,
        InitiateOAuthUseCase $useCase,
        string $provider,
    ): JsonResponse {
        $output = $useCase->execute(new InitiateOAuthInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            provider: $provider,
            scopes: $request->validated('scopes', []),
        ));

        return ApiResponse::success(OAuthInitResource::fromOutput($output)->toArray());
    }

    public function handleCallback(
        HandleOAuthCallbackRequest $request,
        HandleOAuthCallbackUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new HandleOAuthCallbackInput(
            code: $request->validated('code'),
            state: $request->validated('state'),
        ));

        return ApiResponse::success(SocialAccountResource::fromOutput($output)->toArray(), status: 201);
    }

    public function list(Request $request, ListSocialAccountsUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute($request->attributes->get('auth_organization_id'));

        $data = array_map(
            fn ($account) => SocialAccountResource::fromOutput($account)->toArray(),
            $output->accounts,
        );

        return ApiResponse::success($data);
    }

    public function disconnect(
        Request $request,
        DisconnectSocialAccountUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(new DisconnectSocialAccountInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            accountId: $id,
        ));

        return ApiResponse::noContent();
    }

    public function refreshToken(
        Request $request,
        RefreshSocialTokenUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new RefreshSocialTokenInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            accountId: $id,
        ));

        return ApiResponse::success(SocialAccountResource::fromOutput($output)->toArray());
    }

    public function checkHealth(
        Request $request,
        CheckAccountHealthUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new CheckAccountHealthInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            accountId: $id,
        ));

        return ApiResponse::success(AccountHealthResource::fromOutput($output)->toArray());
    }
}
