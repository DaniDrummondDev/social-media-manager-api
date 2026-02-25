<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Controllers;

use App\Application\PlatformAdmin\DTOs\CreatePlanInput;
use App\Application\PlatformAdmin\DTOs\ListPlanSubscribersInput;
use App\Application\PlatformAdmin\DTOs\UpdatePlanInput;
use App\Application\PlatformAdmin\UseCases\CreatePlanUseCase;
use App\Application\PlatformAdmin\UseCases\DeactivatePlanUseCase;
use App\Application\PlatformAdmin\UseCases\ListPlanSubscribersUseCase;
use App\Application\PlatformAdmin\UseCases\ListPlansAdminUseCase;
use App\Application\PlatformAdmin\UseCases\UpdatePlanUseCase;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Infrastructure\PlatformAdmin\Requests\CreatePlanRequest;
use App\Infrastructure\PlatformAdmin\Requests\UpdatePlanRequest;
use App\Infrastructure\PlatformAdmin\Resources\AdminOrganizationResource;
use App\Infrastructure\PlatformAdmin\Resources\AdminPlanResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminPlanController
{
    public function index(
        Request $request,
        ListPlansAdminUseCase $useCase,
    ): JsonResponse {
        $plans = $useCase->execute();

        return ApiResponse::success(
            array_map(fn ($item) => AdminPlanResource::fromOutput($item)->toArray(), $plans),
        );
    }

    public function store(
        CreatePlanRequest $request,
        CreatePlanUseCase $useCase,
    ): JsonResponse {
        $planId = $useCase->execute(
            new CreatePlanInput(
                name: $request->validated('name'),
                slug: $request->validated('slug'),
                description: $request->validated('description'),
                priceMonthly: (int) $request->validated('price_monthly_cents'),
                priceYearly: (int) $request->validated('price_yearly_cents'),
                currency: $request->validated('currency'),
                limits: $request->validated('limits'),
                features: $request->validated('features'),
                sortOrder: (int) $request->validated('sort_order'),
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::success(
            ['id' => $planId, 'type' => 'plan'],
            status: 201,
        );
    }

    public function update(
        string $id,
        UpdatePlanRequest $request,
        UpdatePlanUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            new UpdatePlanInput(
                planId: $id,
                name: $request->validated('name'),
                description: $request->validated('description'),
                priceMonthly: $request->has('price_monthly_cents') ? (int) $request->validated('price_monthly_cents') : null,
                priceYearly: $request->has('price_yearly_cents') ? (int) $request->validated('price_yearly_cents') : null,
                limits: $request->validated('limits'),
                features: $request->validated('features'),
                sortOrder: $request->has('sort_order') ? (int) $request->validated('sort_order') : null,
            ),
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }

    public function deactivate(
        string $id,
        Request $request,
        DeactivatePlanUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(
            $id,
            PlatformRole::from($request->attributes->get('auth_platform_role')),
            $request->attributes->get('auth_admin_id'),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return ApiResponse::noContent();
    }

    public function subscribers(
        string $id,
        Request $request,
        ListPlanSubscribersUseCase $useCase,
    ): JsonResponse {
        $input = new ListPlanSubscribersInput(
            planId: $id,
            subscriptionStatus: $request->query('subscription_status'),
            sort: $request->query('sort', '-created_at'),
            perPage: (int) $request->query('per_page', 20),
            cursor: $request->query('cursor'),
        );

        $result = $useCase->execute($input);

        return ApiResponse::success(
            array_map(fn ($item) => AdminOrganizationResource::fromOutput($item)->toArray(), $result['items']),
            ['per_page' => $input->perPage, 'has_more' => $result['has_more'], 'next_cursor' => $result['next_cursor']],
        );
    }
}
