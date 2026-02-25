<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\CreatePlanInput;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\CreatePlanUseCase;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates a plan successfully as super admin', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';
    $newPlanId = '00000000-0000-4000-a000-000000000050';

    $planRepository->shouldReceive('findBySlug')
        ->with('enterprise')
        ->once()
        ->andReturn(null);

    $queryService->shouldReceive('createPlan')
        ->with(Mockery::on(fn (array $data) => $data['name'] === 'Enterprise'
            && $data['slug'] === 'enterprise'
            && $data['price_monthly_amount_cents'] === 29900
            && $data['price_yearly_amount_cents'] === 299000
            && $data['currency'] === 'BRL'))
        ->once()
        ->andReturn($newPlanId);

    $auditService->shouldReceive('log')
        ->with(
            $adminId,
            'plan.created',
            'plan',
            $newPlanId,
            Mockery::on(fn (array $ctx) => $ctx['name'] === 'Enterprise'
                && $ctx['slug'] === 'enterprise'
                && $ctx['price_monthly_cents'] === 29900
                && $ctx['price_yearly_cents'] === 299000),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new CreatePlanUseCase($planRepository, $queryService, $auditService);
    $result = $useCase->execute(
        new CreatePlanInput(
            name: 'Enterprise',
            slug: 'enterprise',
            description: 'Enterprise plan for large teams',
            priceMonthly: 29900,
            priceYearly: 299000,
            currency: 'BRL',
            limits: ['members' => 50, 'social_accounts' => 100],
            features: ['ai_generation_advanced' => true],
            sortOrder: 5,
        ),
        PlatformRole::SuperAdmin,
        $adminId,
        '127.0.0.1',
        'TestAgent',
    );

    expect($result)->toBe($newPlanId);
});

it('throws InsufficientAdminPrivilegeException when role is admin', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $useCase = new CreatePlanUseCase($planRepository, $queryService, $auditService);
    $useCase->execute(
        new CreatePlanInput(
            name: 'Enterprise',
            slug: 'enterprise',
            description: null,
            priceMonthly: 29900,
            priceYearly: 299000,
        ),
        PlatformRole::Admin,
        $adminId,
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('throws InsufficientAdminPrivilegeException when role is support', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $useCase = new CreatePlanUseCase($planRepository, $queryService, $auditService);
    $useCase->execute(
        new CreatePlanInput(
            name: 'Enterprise',
            slug: 'enterprise',
            description: null,
            priceMonthly: 29900,
            priceYearly: 299000,
        ),
        PlatformRole::Support,
        $adminId,
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('throws DomainException when slug already exists', function () {
    $planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $now = new DateTimeImmutable;
    $existingPlan = Plan::reconstitute(
        id: Uuid::generate(),
        name: 'Professional',
        slug: 'professional',
        description: 'Professional plan',
        priceMonthly: Money::fromCents(14900),
        priceYearly: Money::fromCents(149000),
        limits: PlanLimits::fromArray([]),
        features: PlanFeatures::fromArray([]),
        isActive: true,
        sortOrder: 3,
        stripePriceMonthlyId: null,
        stripePriceYearlyId: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $planRepository->shouldReceive('findBySlug')
        ->with('professional')
        ->once()
        ->andReturn($existingPlan);

    $useCase = new CreatePlanUseCase($planRepository, $queryService, $auditService);
    $useCase->execute(
        new CreatePlanInput(
            name: 'Professional Duplicate',
            slug: 'professional',
            description: null,
            priceMonthly: 14900,
            priceYearly: 149000,
        ),
        PlatformRole::SuperAdmin,
        $adminId,
        '127.0.0.1',
        null,
    );
})->throws(DomainException::class, "Já existe um plano com o slug 'professional'.");
