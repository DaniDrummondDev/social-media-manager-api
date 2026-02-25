<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Controllers;

use App\Application\ClientFinance\DTOs\CreateContractInput;
use App\Application\ClientFinance\DTOs\ListContractsInput;
use App\Application\ClientFinance\DTOs\UpdateContractInput;
use App\Application\ClientFinance\UseCases\CompleteContractUseCase;
use App\Application\ClientFinance\UseCases\CreateContractUseCase;
use App\Application\ClientFinance\UseCases\ListContractsUseCase;
use App\Application\ClientFinance\UseCases\PauseContractUseCase;
use App\Application\ClientFinance\UseCases\UpdateContractUseCase;
use App\Infrastructure\ClientFinance\Requests\CreateContractRequest;
use App\Infrastructure\ClientFinance\Requests\ListContractsRequest;
use App\Infrastructure\ClientFinance\Requests\UpdateContractRequest;
use App\Infrastructure\ClientFinance\Resources\ContractResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientContractController
{
    public function store(
        CreateContractRequest $request,
        string $clientId,
        CreateContractUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateContractInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            clientId: $clientId,
            name: $request->validated('name'),
            type: $request->validated('type'),
            valueCents: (int) $request->validated('value_cents'),
            currency: $request->validated('currency') ?? 'BRL',
            startsAt: $request->validated('starts_at'),
            endsAt: $request->validated('ends_at'),
            socialAccountIds: $request->validated('social_account_ids') ?? [],
        ));

        return ApiResponse::success(
            ContractResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        ListContractsRequest $request,
        string $clientId,
        ListContractsUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListContractsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            clientId: $clientId,
            status: $request->validated('status'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => ContractResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function update(
        UpdateContractRequest $request,
        string $contractId,
        UpdateContractUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateContractInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            contractId: $contractId,
            name: $request->validated('name'),
            valueCents: $request->validated('value_cents') !== null ? (int) $request->validated('value_cents') : null,
            endsAt: $request->validated('ends_at'),
            socialAccountIds: $request->validated('social_account_ids'),
        ));

        return ApiResponse::success(
            ContractResource::fromOutput($output)->toArray(),
        );
    }

    public function pause(
        Request $request,
        string $contractId,
        PauseContractUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(
            $contractId,
            $request->attributes->get('auth_organization_id'),
        );

        return ApiResponse::success(
            ContractResource::fromOutput($output)->toArray(),
        );
    }

    public function complete(
        Request $request,
        string $contractId,
        CompleteContractUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(
            $contractId,
            $request->attributes->get('auth_organization_id'),
            $request->attributes->get('auth_user_id'),
        );

        return ApiResponse::success(
            ContractResource::fromOutput($output)->toArray(),
        );
    }
}
