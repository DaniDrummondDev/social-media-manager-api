<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\CreatePlanInput;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;

final class CreatePlanUseCase
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        CreatePlanInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): string {
        if (! $role->canManagePlans()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $existing = $this->planRepository->findBySlug($input->slug);

        if ($existing !== null) {
            throw new DomainException(
                "Já existe um plano com o slug '{$input->slug}'.",
                'PLAN_SLUG_ALREADY_EXISTS',
            );
        }

        $planId = $this->queryService->createPlan([
            'name' => $input->name,
            'slug' => $input->slug,
            'description' => $input->description,
            'price_monthly_amount_cents' => $input->priceMonthly,
            'price_yearly_amount_cents' => $input->priceYearly,
            'currency' => $input->currency,
            'limits' => $input->limits,
            'features' => $input->features,
            'sort_order' => $input->sortOrder,
        ]);

        $this->auditService->log(
            adminId: $adminId,
            action: 'plan.created',
            resourceType: 'plan',
            resourceId: $planId,
            context: [
                'name' => $input->name,
                'slug' => $input->slug,
                'price_monthly_cents' => $input->priceMonthly,
                'price_yearly_cents' => $input->priceYearly,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        return $planId;
    }
}
