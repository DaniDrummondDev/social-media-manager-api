<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controllers;

use App\Application\Billing\DTOs\CancelSubscriptionInput;
use App\Application\Billing\DTOs\CreateCheckoutSessionInput;
use App\Application\Billing\DTOs\CreatePortalSessionInput;
use App\Application\Billing\DTOs\ListInvoicesInput;
use App\Application\Billing\DTOs\ReactivateSubscriptionInput;
use App\Application\Billing\UseCases\CancelSubscriptionUseCase;
use App\Application\Billing\UseCases\CreateCheckoutSessionUseCase;
use App\Application\Billing\UseCases\CreatePortalSessionUseCase;
use App\Application\Billing\UseCases\GetSubscriptionUseCase;
use App\Application\Billing\UseCases\GetUsageUseCase;
use App\Application\Billing\UseCases\ListInvoicesUseCase;
use App\Application\Billing\UseCases\ReactivateSubscriptionUseCase;
use App\Infrastructure\Billing\Requests\CancelSubscriptionRequest;
use App\Infrastructure\Billing\Requests\CreateCheckoutRequest;
use App\Infrastructure\Billing\Requests\CreatePortalRequest;
use App\Infrastructure\Billing\Requests\ListInvoicesRequest;
use App\Infrastructure\Billing\Resources\InvoiceResource;
use App\Infrastructure\Billing\Resources\SubscriptionResource;
use App\Infrastructure\Billing\Resources\UsageResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BillingController
{
    public function subscription(Request $request, GetSubscriptionUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
        );

        return ApiResponse::success(
            SubscriptionResource::fromOutput($output)->toArray(),
        );
    }

    public function usage(Request $request, GetUsageUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
        );

        return ApiResponse::success(
            UsageResource::fromOutput($output)->toArray(),
        );
    }

    public function invoices(ListInvoicesRequest $request, ListInvoicesUseCase $useCase): JsonResponse
    {
        $invoices = $useCase->execute(new ListInvoicesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            status: $request->validated('status'),
            from: $request->validated('from'),
            to: $request->validated('to'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('per_page') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($i) => InvoiceResource::fromOutput($i)->toArray(), $invoices),
        );
    }

    public function checkout(CreateCheckoutRequest $request, CreateCheckoutSessionUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new CreateCheckoutSessionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            planSlug: $request->validated('plan_slug'),
            billingCycle: $request->validated('billing_cycle'),
            successUrl: $request->validated('success_url'),
            cancelUrl: $request->validated('cancel_url'),
        ));

        return ApiResponse::success([
            'checkout_url' => $output->checkoutUrl,
            'session_id' => $output->sessionId,
            'expires_at' => $output->expiresAt,
        ], status: 201);
    }

    public function portal(CreatePortalRequest $request, CreatePortalSessionUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new CreatePortalSessionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            returnUrl: $request->validated('return_url'),
        ));

        return ApiResponse::success([
            'portal_url' => $output->portalUrl,
        ]);
    }

    public function cancel(CancelSubscriptionRequest $request, CancelSubscriptionUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new CancelSubscriptionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            reason: $request->validated('reason'),
            feedback: $request->validated('feedback'),
        ));

        return ApiResponse::success(
            SubscriptionResource::fromOutput($output)->toArray(),
        );
    }

    public function reactivate(Request $request, ReactivateSubscriptionUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new ReactivateSubscriptionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
        ));

        return ApiResponse::success(
            SubscriptionResource::fromOutput($output)->toArray(),
        );
    }
}
