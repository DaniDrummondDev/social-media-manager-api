<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Controllers;

use App\Application\ClientFinance\DTOs\AllocateCostInput;
use App\Application\ClientFinance\DTOs\GetCostBreakdownInput;
use App\Application\ClientFinance\UseCases\AllocateCostUseCase;
use App\Application\ClientFinance\UseCases\GetCostBreakdownUseCase;
use App\Infrastructure\ClientFinance\Requests\AllocateCostRequest;
use App\Infrastructure\ClientFinance\Requests\ListCostAllocationsRequest;
use App\Infrastructure\ClientFinance\Resources\CostAllocationResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class CostAllocationController
{
    public function store(AllocateCostRequest $request, AllocateCostUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new AllocateCostInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            clientId: $request->validated('client_id'),
            resourceType: $request->validated('resource_type'),
            resourceId: $request->validated('resource_id'),
            description: $request->validated('description'),
            costCents: (int) $request->validated('cost_cents'),
            currency: $request->validated('currency') ?? 'BRL',
        ));

        return ApiResponse::success(
            CostAllocationResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(ListCostAllocationsRequest $request, GetCostBreakdownUseCase $useCase): JsonResponse
    {
        $result = $useCase->execute(new GetCostBreakdownInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            clientId: $request->validated('client_id'),
            resourceType: $request->validated('resource_type'),
            from: $request->validated('from'),
            to: $request->validated('to'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => CostAllocationResource::fromOutput($item)->toArray(), $result->items),
            ['next_cursor' => $result->nextCursor],
        );
    }
}
