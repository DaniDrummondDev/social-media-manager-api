<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Controllers;

use App\Application\PaidAdvertising\DTOs\CancelBoostInput;
use App\Application\PaidAdvertising\DTOs\CreateBoostInput;
use App\Application\PaidAdvertising\DTOs\GetBoostInput;
use App\Application\PaidAdvertising\DTOs\GetBoostMetricsInput;
use App\Application\PaidAdvertising\DTOs\ListBoostsInput;
use App\Application\PaidAdvertising\UseCases\CancelBoostUseCase;
use App\Application\PaidAdvertising\UseCases\CreateBoostUseCase;
use App\Application\PaidAdvertising\UseCases\GetBoostMetricsUseCase;
use App\Application\PaidAdvertising\UseCases\GetBoostUseCase;
use App\Application\PaidAdvertising\UseCases\ListBoostsUseCase;
use App\Infrastructure\PaidAdvertising\Requests\CreateBoostRequest;
use App\Infrastructure\PaidAdvertising\Resources\BoostMetricsResource;
use App\Infrastructure\PaidAdvertising\Resources\BoostResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdBoostController
{
    public function store(
        CreateBoostRequest $request,
        CreateBoostUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateBoostInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            scheduledPostId: $request->validated('scheduled_post_id'),
            adAccountId: $request->validated('ad_account_id'),
            audienceId: $request->validated('audience_id'),
            budgetAmountCents: $request->validated('budget_amount_cents'),
            budgetCurrency: $request->validated('budget_currency'),
            budgetType: $request->validated('budget_type'),
            durationDays: $request->validated('duration_days'),
            objective: $request->validated('objective'),
        ));

        return ApiResponse::success(
            BoostResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        ListBoostsUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListBoostsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            status: $request->query('status'),
            cursor: $request->query('cursor'),
            limit: min((int) $request->query('limit', 20), 100),
        ));

        $data = array_map(
            fn ($item) => BoostResource::fromOutput($item)->toArray(),
            $result['items'],
        );

        return ApiResponse::success($data, [
            'pagination' => [
                'next_cursor' => $result['next_cursor'],
            ],
        ]);
    }

    public function show(
        Request $request,
        GetBoostUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new GetBoostInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            boostId: $id,
        ));

        return ApiResponse::success(
            BoostResource::fromOutput($output)->toArray(),
        );
    }

    public function cancel(
        Request $request,
        CancelBoostUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new CancelBoostInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            boostId: $id,
        ));

        return ApiResponse::success(
            BoostResource::fromOutput($output)->toArray(),
        );
    }

    public function metrics(
        Request $request,
        GetBoostMetricsUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new GetBoostMetricsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            boostId: $id,
            period: $request->query('period'),
        ));

        return ApiResponse::success(
            BoostMetricsResource::fromOutput($output)->toArray(),
        );
    }
}
