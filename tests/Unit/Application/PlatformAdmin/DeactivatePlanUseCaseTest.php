<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\DeactivatePlanUseCase;
use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

function createDeactivatePlan(bool $isFree = false): Plan
{
    $now = new DateTimeImmutable;

    return Plan::reconstitute(
        id: Uuid::generate(),
        name: $isFree ? 'Free' : 'Professional',
        slug: $isFree ? 'free' : 'professional',
        description: 'Test plan',
        priceMonthly: $isFree ? Money::fromCents(0) : Money::fromCents(9900),
        priceYearly: $isFree ? Money::fromCents(0) : Money::fromCents(99000),
        limits: PlanLimits::fromArray([
            'social_accounts' => 5,
            'active_campaigns' => 10,
            'publications_month' => 100,
            'storage_gb' => 1,
            'members' => 5,
            'ai_generations_month' => 50,
        ]),
        features: PlanFeatures::fromArray([
            'ai_generation_basic' => true,
            'ai_generation_advanced' => false,
            'automations' => true,
        ]),
        isActive: true,
        sortOrder: 1,
        stripePriceMonthlyId: $isFree ? null : 'price_monthly_123',
        stripePriceYearlyId: $isFree ? null : 'price_yearly_123',
        createdAt: $now,
        updatedAt: $now,
    );
}

it('deactivates a paid plan successfully', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $plan = createDeactivatePlan(isFree: false);
    $planId = (string) $plan->id;

    $planRepository->shouldReceive('findById')
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === $planId))
        ->once()
        ->andReturn($plan);

    $queryService->shouldReceive('countPlanSubscribers')
        ->with($planId)
        ->once()
        ->andReturn(15);

    $queryService->shouldReceive('deactivatePlan')
        ->with($planId)
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'plan.deactivated',
            'plan',
            $planId,
            Mockery::on(fn (array $ctx) => $ctx['plan_name'] === 'Professional'
                && $ctx['plan_slug'] === 'professional'
                && $ctx['active_subscribers'] === 15),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new DeactivatePlanUseCase($planRepository, $queryService, $auditService);
    $useCase->execute(
        $planId,
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('throws DomainException when plan is not found', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $planId = '00000000-0000-4000-a000-000000000099';

    $planRepository->shouldReceive('findById')
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === $planId))
        ->once()
        ->andReturn(null);

    $useCase = new DeactivatePlanUseCase($planRepository, $queryService, $auditService);
    $useCase->execute(
        $planId,
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(DomainException::class, 'Plano não encontrado.');

it('throws DomainException when trying to deactivate free plan', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $plan = createDeactivatePlan(isFree: true);
    $planId = (string) $plan->id;

    $planRepository->shouldReceive('findById')
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === $planId))
        ->once()
        ->andReturn($plan);

    $useCase = new DeactivatePlanUseCase($planRepository, $queryService, $auditService);
    $useCase->execute(
        $planId,
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(DomainException::class, 'O plano gratuito não pode ser desativado.');

it('throws InsufficientAdminPrivilegeException when role is support', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $planId = '00000000-0000-4000-a000-000000000020';

    $useCase = new DeactivatePlanUseCase($planRepository, $queryService, $auditService);
    $useCase->execute(
        $planId,
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);
