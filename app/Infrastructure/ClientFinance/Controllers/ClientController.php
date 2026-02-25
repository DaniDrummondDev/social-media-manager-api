<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Controllers;

use App\Application\ClientFinance\DTOs\CreateClientInput;
use App\Application\ClientFinance\DTOs\ListClientsInput;
use App\Application\ClientFinance\DTOs\UpdateClientInput;
use App\Application\ClientFinance\UseCases\ArchiveClientUseCase;
use App\Application\ClientFinance\UseCases\CreateClientUseCase;
use App\Application\ClientFinance\UseCases\GetClientUseCase;
use App\Application\ClientFinance\UseCases\ListClientsUseCase;
use App\Application\ClientFinance\UseCases\UpdateClientUseCase;
use App\Infrastructure\ClientFinance\Requests\CreateClientRequest;
use App\Infrastructure\ClientFinance\Requests\ListClientsRequest;
use App\Infrastructure\ClientFinance\Requests\UpdateClientRequest;
use App\Infrastructure\ClientFinance\Resources\ClientResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientController
{
    public function store(CreateClientRequest $request, CreateClientUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new CreateClientInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            companyName: $request->validated('company_name'),
            taxId: $request->validated('tax_id'),
            billingAddress: $request->validated('billing_address'),
            notes: $request->validated('notes'),
        ));

        return ApiResponse::success(
            ClientResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(ListClientsRequest $request, ListClientsUseCase $useCase): JsonResponse
    {
        $result = $useCase->execute(new ListClientsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            status: $request->validated('status'),
            search: $request->validated('search'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => ClientResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function show(Request $request, string $id, GetClientUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(
            $id,
            $request->attributes->get('auth_organization_id'),
        );

        return ApiResponse::success(
            ClientResource::fromOutput($output)->toArray(),
        );
    }

    public function update(UpdateClientRequest $request, string $id, UpdateClientUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new UpdateClientInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            clientId: $id,
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            companyName: $request->validated('company_name'),
            taxId: $request->validated('tax_id'),
            billingAddress: $request->validated('billing_address'),
            notes: $request->validated('notes'),
            status: $request->validated('status'),
        ));

        return ApiResponse::success(
            ClientResource::fromOutput($output)->toArray(),
        );
    }

    public function archive(Request $request, string $id, ArchiveClientUseCase $useCase): JsonResponse
    {
        $useCase->execute(
            $id,
            $request->attributes->get('auth_organization_id'),
            $request->attributes->get('auth_user_id'),
        );

        return ApiResponse::success(
            ['archived' => true],
        );
    }
}
