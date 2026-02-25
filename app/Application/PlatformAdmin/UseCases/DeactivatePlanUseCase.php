<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeactivatePlanUseCase
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        string $planId,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canManagePlans()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $plan = $this->planRepository->findById(Uuid::fromString($planId));

        if ($plan === null) {
            throw new DomainException(
                'Plano não encontrado.',
                'PLAN_NOT_FOUND',
            );
        }

        if ($plan->isFree()) {
            throw new DomainException(
                'O plano gratuito não pode ser desativado.',
                'CANNOT_DEACTIVATE_FREE_PLAN',
            );
        }

        $subscribersCount = $this->queryService->countPlanSubscribers($planId);

        $this->queryService->deactivatePlan($planId);

        $this->auditService->log(
            adminId: $adminId,
            action: 'plan.deactivated',
            resourceType: 'plan',
            resourceId: $planId,
            context: [
                'plan_name' => $plan->name,
                'plan_slug' => $plan->slug,
                'active_subscribers' => $subscribersCount,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
