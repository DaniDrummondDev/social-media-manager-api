<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Controllers;

use App\Application\Engagement\DTOs\CreateWebhookInput;
use App\Application\Engagement\DTOs\ListDeliveriesInput;
use App\Application\Engagement\DTOs\TestWebhookInput;
use App\Application\Engagement\DTOs\UpdateWebhookInput;
use App\Application\Engagement\UseCases\CreateWebhookUseCase;
use App\Application\Engagement\UseCases\DeleteWebhookUseCase;
use App\Application\Engagement\UseCases\ListDeliveriesUseCase;
use App\Application\Engagement\UseCases\ListWebhooksUseCase;
use App\Application\Engagement\UseCases\TestWebhookUseCase;
use App\Application\Engagement\UseCases\UpdateWebhookUseCase;
use App\Infrastructure\Engagement\Requests\CreateWebhookRequest;
use App\Infrastructure\Engagement\Requests\UpdateWebhookRequest;
use App\Infrastructure\Engagement\Resources\WebhookDeliveryResource;
use App\Infrastructure\Engagement\Resources\WebhookResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebhookController
{
    public function store(
        CreateWebhookRequest $request,
        CreateWebhookUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateWebhookInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            url: $request->validated('url'),
            events: $request->validated('events'),
            headers: $request->validated('headers'),
        ));

        return ApiResponse::success(
            WebhookResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        ListWebhooksUseCase $useCase,
    ): JsonResponse {
        $webhooks = $useCase->execute($request->attributes->get('auth_organization_id'));

        $data = array_map(
            fn ($item) => WebhookResource::fromOutput($item)->toArray(),
            $webhooks,
        );

        return ApiResponse::success($data);
    }

    public function update(
        UpdateWebhookRequest $request,
        UpdateWebhookUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateWebhookInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            webhookId: $id,
            name: $request->validated('name'),
            url: $request->validated('url'),
            events: $request->validated('events'),
            headers: $request->validated('headers'),
            isActive: $request->has('is_active') ? (bool) $request->validated('is_active') : null,
        ));

        return ApiResponse::success(
            WebhookResource::fromOutput($output)->toArray(),
        );
    }

    public function destroy(
        Request $request,
        DeleteWebhookUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute($request->attributes->get('auth_organization_id'), $id);

        return ApiResponse::success(null, status: 204);
    }

    public function test(
        Request $request,
        TestWebhookUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new TestWebhookInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            webhookId: $id,
        ));

        return ApiResponse::success([
            'success' => $output->success,
            'response_status' => $output->responseStatus,
            'response_time_ms' => $output->responseTimeMs,
        ]);
    }

    public function deliveries(
        Request $request,
        ListDeliveriesUseCase $useCase,
        string $id,
    ): JsonResponse {
        $deliveries = $useCase->execute(new ListDeliveriesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            webhookId: $id,
            cursor: $request->query('cursor'),
            limit: (int) ($request->query('limit') ?? 20),
        ));

        $data = array_map(
            fn ($item) => WebhookDeliveryResource::fromOutput($item)->toArray(),
            $deliveries,
        );

        return ApiResponse::success($data);
    }
}
