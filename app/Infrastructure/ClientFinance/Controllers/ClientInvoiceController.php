<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Controllers;

use App\Application\ClientFinance\DTOs\CancelInvoiceInput;
use App\Application\ClientFinance\DTOs\GenerateInvoiceInput;
use App\Application\ClientFinance\DTOs\ListInvoicesInput;
use App\Application\ClientFinance\DTOs\MarkInvoicePaidInput;
use App\Application\ClientFinance\DTOs\SendInvoiceInput;
use App\Application\ClientFinance\DTOs\UpdateInvoiceInput;
use App\Application\ClientFinance\UseCases\CancelInvoiceUseCase;
use App\Application\ClientFinance\UseCases\GenerateInvoiceUseCase;
use App\Application\ClientFinance\UseCases\GetInvoiceUseCase;
use App\Application\ClientFinance\UseCases\ListInvoicesUseCase;
use App\Application\ClientFinance\UseCases\MarkInvoicePaidUseCase;
use App\Application\ClientFinance\UseCases\SendInvoiceUseCase;
use App\Application\ClientFinance\UseCases\UpdateInvoiceUseCase;
use App\Infrastructure\ClientFinance\Requests\GenerateInvoiceRequest;
use App\Infrastructure\ClientFinance\Requests\ListInvoicesRequest;
use App\Infrastructure\ClientFinance\Requests\MarkInvoicePaidRequest;
use App\Infrastructure\ClientFinance\Requests\UpdateInvoiceRequest;
use App\Infrastructure\ClientFinance\Resources\InvoiceResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientInvoiceController
{
    public function store(GenerateInvoiceRequest $request, GenerateInvoiceUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new GenerateInvoiceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            clientId: $request->validated('client_id'),
            contractId: $request->validated('contract_id'),
            referenceMonth: $request->validated('reference_month'),
            items: $request->validated('items'),
            discountCents: (int) ($request->validated('discount_cents') ?? 0),
            currency: $request->validated('currency') ?? 'BRL',
            dueDate: $request->validated('due_date'),
            notes: $request->validated('notes'),
        ));

        return ApiResponse::success(
            InvoiceResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(ListInvoicesRequest $request, ListInvoicesUseCase $useCase): JsonResponse
    {
        $result = $useCase->execute(new ListInvoicesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            clientId: $request->validated('client_id'),
            status: $request->validated('status'),
            referenceMonth: $request->validated('reference_month'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => InvoiceResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function show(Request $request, string $invoiceId, GetInvoiceUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(
            $invoiceId,
            $request->attributes->get('auth_organization_id'),
        );

        return ApiResponse::success(
            InvoiceResource::fromOutput($output)->toArray(),
        );
    }

    public function update(
        UpdateInvoiceRequest $request,
        string $invoiceId,
        UpdateInvoiceUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateInvoiceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            invoiceId: $invoiceId,
            items: $request->validated('items'),
            discountCents: (int) ($request->validated('discount_cents') ?? 0),
            notes: $request->validated('notes'),
            dueDate: $request->validated('due_date'),
        ));

        return ApiResponse::success(
            InvoiceResource::fromOutput($output)->toArray(),
        );
    }

    public function send(
        Request $request,
        string $invoiceId,
        SendInvoiceUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new SendInvoiceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            invoiceId: $invoiceId,
        ));

        return ApiResponse::success(
            InvoiceResource::fromOutput($output)->toArray(),
        );
    }

    public function markPaid(
        MarkInvoicePaidRequest $request,
        string $invoiceId,
        MarkInvoicePaidUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new MarkInvoicePaidInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            invoiceId: $invoiceId,
            paymentMethod: $request->validated('payment_method'),
            paymentNotes: $request->validated('payment_notes'),
        ));

        return ApiResponse::success(
            InvoiceResource::fromOutput($output)->toArray(),
        );
    }

    public function cancel(
        Request $request,
        string $invoiceId,
        CancelInvoiceUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CancelInvoiceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            invoiceId: $invoiceId,
        ));

        return ApiResponse::success(
            InvoiceResource::fromOutput($output)->toArray(),
        );
    }
}
