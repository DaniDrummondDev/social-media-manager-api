<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Controllers;

use App\Application\SocialListening\DTOs\CreateAlertInput;
use App\Application\SocialListening\DTOs\DeleteAlertInput;
use App\Application\SocialListening\DTOs\ListAlertsInput;
use App\Application\SocialListening\DTOs\UpdateAlertInput;
use App\Application\SocialListening\UseCases\CreateAlertUseCase;
use App\Application\SocialListening\UseCases\DeleteAlertUseCase;
use App\Application\SocialListening\UseCases\ListAlertsUseCase;
use App\Application\SocialListening\UseCases\UpdateAlertUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use App\Infrastructure\SocialListening\Requests\CreateAlertRequest;
use App\Infrastructure\SocialListening\Requests\ListAlertsRequest;
use App\Infrastructure\SocialListening\Requests\UpdateAlertRequest;
use App\Infrastructure\SocialListening\Resources\ListeningAlertResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListeningAlertController
{
    public function store(
        CreateAlertRequest $request,
        CreateAlertUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateAlertInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            queryIds: $request->validated('query_ids'),
            conditionType: $request->validated('condition_type'),
            threshold: (int) $request->validated('threshold'),
            windowMinutes: (int) $request->validated('window_minutes'),
            channels: $request->validated('channels'),
            cooldownMinutes: (int) $request->validated('cooldown_minutes'),
        ));

        return ApiResponse::success(
            ListeningAlertResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        ListAlertsRequest $request,
        ListAlertsUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListAlertsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => ListeningAlertResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function update(
        UpdateAlertRequest $request,
        string $alertId,
        UpdateAlertUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateAlertInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            alertId: $alertId,
            name: $request->validated('name'),
            queryIds: $request->validated('query_ids'),
            conditionType: $request->validated('condition_type'),
            threshold: $request->validated('threshold') !== null ? (int) $request->validated('threshold') : null,
            windowMinutes: $request->validated('window_minutes') !== null ? (int) $request->validated('window_minutes') : null,
            channels: $request->validated('channels'),
            cooldownMinutes: $request->validated('cooldown_minutes') !== null ? (int) $request->validated('cooldown_minutes') : null,
        ));

        return ApiResponse::success(
            ListeningAlertResource::fromOutput($output)->toArray(),
        );
    }

    public function destroy(
        Request $request,
        string $alertId,
        DeleteAlertUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(new DeleteAlertInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            alertId: $alertId,
        ));

        return ApiResponse::noContent();
    }
}
