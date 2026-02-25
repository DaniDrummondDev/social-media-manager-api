<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\UpdatePlanInput;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdatePlanUseCase
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        UpdatePlanInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canManagePlans()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $plan = $this->planRepository->findById(Uuid::fromString($input->planId));

        if ($plan === null) {
            throw new DomainException(
                'Plano não encontrado.',
                'PLAN_NOT_FOUND',
            );
        }

        $data = array_filter([
            'name' => $input->name,
            'description' => $input->description,
            'price_monthly_amount_cents' => $input->priceMonthly,
            'price_yearly_amount_cents' => $input->priceYearly,
            'limits' => $input->limits,
            'features' => $input->features,
            'sort_order' => $input->sortOrder,
        ], fn ($v) => $v !== null);

        $this->queryService->updatePlan($input->planId, $data);

        $this->auditService->log(
            adminId: $adminId,
            action: 'plan.updated',
            resourceType: 'plan',
            resourceId: $input->planId,
            context: [
                'plan_name' => $plan->name,
                'updated_fields' => array_keys($data),
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
