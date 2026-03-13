<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\UpdatePlanInput;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\UpdatePlanUseCase;
use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->planRepository = Mockery::mock(PlanRepositoryInterface::class);
    $this->queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $this->auditService = Mockery::mock(AuditServiceInterface::class);

    $this->useCase = new UpdatePlanUseCase(
        $this->planRepository,
        $this->queryService,
        $this->auditService,
    );

    $this->adminId = 'admin-uuid';
    $this->ipAddress = '127.0.0.1';
    $this->userAgent = 'TestAgent';

    $this->now = new DateTimeImmutable;
});

function createTestPlan(DateTimeImmutable $now): Plan
{
    return Plan::reconstitute(
        id: Uuid::fromString('00000000-0000-4000-a000-000000000050'),
        name: 'Professional',
        slug: 'professional',
        description: 'Professional plan',
        priceMonthly: Money::fromCents(14900),
        priceYearly: Money::fromCents(149000),
        limits: PlanLimits::fromArray([
            'max_social_accounts' => 10,
            'max_campaigns' => 50,
        ]),
        features: PlanFeatures::fromArray([
            'has_analytics' => true,
            'has_scheduling' => true,
        ]),
        isActive: true,
        sortOrder: 2,
        stripePriceMonthlyId: null,
        stripePriceYearlyId: null,
        createdAt: $now,
        updatedAt: $now,
    );
}

it('should update plan name successfully', function () {
    $planId = '00000000-0000-4000-a000-000000000050';
    $plan = createTestPlan($this->now);

    $input = new UpdatePlanInput(
        planId: $planId,
        name: 'Professional Plus',
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === $planId))
        ->andReturn($plan);

    $this->queryService
        ->shouldReceive('updatePlan')
        ->once()
        ->with(
            $planId,
            Mockery::on(fn (array $data) => $data['name'] === 'Professional Plus'
                && count($data) === 1)
        );

    $this->auditService
        ->shouldReceive('log')
        ->once()
        ->withArgs(fn ($adminId, $action, $resourceType, $resourceId, $context, $ipAddress, $userAgent) =>
            $adminId === $this->adminId &&
            $action === 'plan.updated' &&
            $resourceType === 'plan' &&
            $resourceId === $planId &&
            $context['plan_name'] === 'Professional' &&
            $context['updated_fields'] === ['name'] &&
            $ipAddress === $this->ipAddress &&
            $userAgent === $this->userAgent
        );

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});

it('should update multiple plan fields successfully', function () {
    $planId = '00000000-0000-4000-a000-000000000050';
    $plan = createTestPlan($this->now);

    $input = new UpdatePlanInput(
        planId: $planId,
        name: 'Professional Plus',
        description: 'Updated description',
        priceMonthly: 19900,
        limits: ['max_social_accounts' => 20],
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $this->queryService
        ->shouldReceive('updatePlan')
        ->once()
        ->with(
            $planId,
            Mockery::on(fn (array $data) => count($data) === 4
                && $data['name'] === 'Professional Plus'
                && $data['description'] === 'Updated description'
                && $data['price_monthly_amount_cents'] === 19900
                && $data['limits'] === ['max_social_accounts' => 20])
        );

    $this->auditService
        ->shouldReceive('log')
        ->once();

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});

it('should update only price fields', function () {
    $planId = '00000000-0000-4000-a000-000000000050';
    $plan = createTestPlan($this->now);

    $input = new UpdatePlanInput(
        planId: $planId,
        priceMonthly: 16900,
        priceYearly: 169000,
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $this->queryService
        ->shouldReceive('updatePlan')
        ->once()
        ->with(
            $planId,
            Mockery::on(fn (array $data) => array_keys($data) === [
                'price_monthly_amount_cents',
                'price_yearly_amount_cents',
            ])
        );

    $this->auditService
        ->shouldReceive('log')
        ->once();

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});

it('should filter out null fields from update', function () {
    $planId = '00000000-0000-4000-a000-000000000050';
    $plan = createTestPlan($this->now);

    $input = new UpdatePlanInput(
        planId: $planId,
        name: 'New Name',
        description: null,
        priceMonthly: null,
        priceYearly: null,
        limits: null,
        features: null,
        sortOrder: null,
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $this->queryService
        ->shouldReceive('updatePlan')
        ->once()
        ->with(
            $planId,
            Mockery::on(fn (array $data) => count($data) === 1
                && $data['name'] === 'New Name')
        );

    $this->auditService
        ->shouldReceive('log')
        ->once();

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});

it('should throw exception when plan not found', function () {
    $planId = '00000000-0000-4000-a000-000000000099';

    $input = new UpdatePlanInput(
        planId: $planId,
        name: 'Test',
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === $planId))
        ->andReturnNull();

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
})->throws(DomainException::class, 'Plano não encontrado.');

it('should throw exception when admin role tries to update plan', function () {
    $planId = '00000000-0000-4000-a000-000000000050';

    $input = new UpdatePlanInput(
        planId: $planId,
        name: 'Test',
    );

    $this->useCase->execute(
        $input,
        PlatformRole::Admin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('should throw exception when support role tries to update plan', function () {
    $planId = '00000000-0000-4000-a000-000000000050';

    $input = new UpdatePlanInput(
        planId: $planId,
        name: 'Test',
    );

    $this->useCase->execute(
        $input,
        PlatformRole::Support,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('should handle null user agent', function () {
    $planId = '00000000-0000-4000-a000-000000000050';
    $plan = createTestPlan($this->now);

    $input = new UpdatePlanInput(
        planId: $planId,
        name: 'Updated Plan',
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $this->queryService
        ->shouldReceive('updatePlan')
        ->once();

    $this->auditService
        ->shouldReceive('log')
        ->once()
        ->withArgs(function ($adminId, $action, $resourceType, $resourceId, $context, $ipAddress, $userAgent) {
            return $userAgent === null;
        });

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        null,
    );
});

it('should update plan with features and limits arrays', function () {
    $planId = '00000000-0000-4000-a000-000000000050';
    $plan = createTestPlan($this->now);

    $newLimits = [
        'max_social_accounts' => 25,
        'max_campaigns' => 100,
        'max_contents_per_month' => 500,
    ];

    $newFeatures = [
        'has_analytics' => true,
        'has_scheduling' => true,
        'has_ai_generation' => true,
        'has_brand_safety' => true,
    ];

    $input = new UpdatePlanInput(
        planId: $planId,
        limits: $newLimits,
        features: $newFeatures,
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $this->queryService
        ->shouldReceive('updatePlan')
        ->once()
        ->with(
            $planId,
            Mockery::on(fn (array $data) => $data['limits'] === $newLimits
                && $data['features'] === $newFeatures)
        );

    $this->auditService
        ->shouldReceive('log')
        ->once();

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});

it('should update sort order successfully', function () {
    $planId = '00000000-0000-4000-a000-000000000050';
    $plan = createTestPlan($this->now);

    $input = new UpdatePlanInput(
        planId: $planId,
        sortOrder: 10,
    );

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $this->queryService
        ->shouldReceive('updatePlan')
        ->once()
        ->with(
            $planId,
            Mockery::on(fn (array $data) => $data['sort_order'] === 10)
        );

    $this->auditService
        ->shouldReceive('log')
        ->once()
        ->withArgs(function ($adminId, $action, $resourceType, $resourceId, $context, $ipAddress, $userAgent) {
            return in_array('sort_order', $context['updated_fields'], true);
        });

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});
