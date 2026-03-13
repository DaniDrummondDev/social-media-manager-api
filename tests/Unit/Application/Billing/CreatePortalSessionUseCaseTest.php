<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\CreatePortalSessionInput;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Application\Billing\UseCases\CreatePortalSessionUseCase;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createPortalTestSubscription(Uuid $orgId, ?string $customerId = 'cus_test_123'): Subscription
{
    $now = new DateTimeImmutable;

    return Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        planId: Uuid::generate(),
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_test_123',
        externalCustomerId: $customerId,
        createdAt: $now,
        updatedAt: $now,
    );
}

it('creates portal session successfully', function () {
    $subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);

    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $subscription = createPortalTestSubscription($orgId);

    $subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => $id->equals($orgId)))
        ->once()
        ->andReturn($subscription);

    $paymentGateway->shouldReceive('createPortalSession')
        ->with(
            'cus_test_123',
            'https://app.example.com/billing',
        )
        ->once()
        ->andReturn([
            'portal_url' => 'https://billing.stripe.com/session/test_123',
        ]);

    $useCase = new CreatePortalSessionUseCase($subscriptionRepo, $paymentGateway);

    $result = $useCase->execute(new CreatePortalSessionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        returnUrl: 'https://app.example.com/billing',
    ));

    expect($result->portalUrl)->toBe('https://billing.stripe.com/session/test_123');
});

it('throws SubscriptionNotFoundException when no active subscription exists', function () {
    $subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);

    $orgId = Uuid::generate();
    $userId = Uuid::generate();

    $subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => $id->equals($orgId)))
        ->once()
        ->andReturn(null);

    $paymentGateway->shouldNotReceive('createPortalSession');

    $useCase = new CreatePortalSessionUseCase($subscriptionRepo, $paymentGateway);

    $useCase->execute(new CreatePortalSessionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        returnUrl: 'https://app.example.com/billing',
    ));
})->throws(SubscriptionNotFoundException::class, 'Assinatura sem integração com gateway de pagamento.');

it('throws SubscriptionNotFoundException when subscription has no external customer ID', function () {
    $subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);

    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $subscription = createPortalTestSubscription($orgId, customerId: null);

    $subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => $id->equals($orgId)))
        ->once()
        ->andReturn($subscription);

    $paymentGateway->shouldNotReceive('createPortalSession');

    $useCase = new CreatePortalSessionUseCase($subscriptionRepo, $paymentGateway);

    $useCase->execute(new CreatePortalSessionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        returnUrl: 'https://app.example.com/billing',
    ));
})->throws(SubscriptionNotFoundException::class, 'Assinatura sem integração com gateway de pagamento.');

it('handles different return URLs correctly', function () {
    $subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);

    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $subscription = createPortalTestSubscription($orgId);
    $returnUrl = 'https://custom-domain.example.com/dashboard/settings/billing';

    $subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => $id->equals($orgId)))
        ->once()
        ->andReturn($subscription);

    $paymentGateway->shouldReceive('createPortalSession')
        ->with(
            'cus_test_123',
            $returnUrl,
        )
        ->once()
        ->andReturn([
            'portal_url' => 'https://billing.stripe.com/session/custom_123',
        ]);

    $useCase = new CreatePortalSessionUseCase($subscriptionRepo, $paymentGateway);

    $result = $useCase->execute(new CreatePortalSessionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        returnUrl: $returnUrl,
    ));

    expect($result->portalUrl)->toBe('https://billing.stripe.com/session/custom_123');
});

it('works with trialing subscription that has customer ID', function () {
    $subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);

    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $now = new DateTimeImmutable;

    $trialingSubscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        planId: Uuid::generate(),
        status: SubscriptionStatus::Trialing,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: new DateTimeImmutable('+14 days'),
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_trial_123',
        externalCustomerId: 'cus_trial_456',
        createdAt: $now,
        updatedAt: $now,
    );

    $subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => $id->equals($orgId)))
        ->once()
        ->andReturn($trialingSubscription);

    $paymentGateway->shouldReceive('createPortalSession')
        ->with(
            'cus_trial_456',
            'https://app.example.com/trial',
        )
        ->once()
        ->andReturn([
            'portal_url' => 'https://billing.stripe.com/session/trial_789',
        ]);

    $useCase = new CreatePortalSessionUseCase($subscriptionRepo, $paymentGateway);

    $result = $useCase->execute(new CreatePortalSessionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        returnUrl: 'https://app.example.com/trial',
    ));

    expect($result->portalUrl)->toBe('https://billing.stripe.com/session/trial_789');
});

it('works with canceled subscription that still has customer ID (graceful access)', function () {
    $subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);

    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $now = new DateTimeImmutable;

    $canceledSubscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        planId: Uuid::generate(),
        status: SubscriptionStatus::Canceled,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: null,
        canceledAt: new DateTimeImmutable('-5 days'),
        cancelAtPeriodEnd: true,
        cancelReason: 'User requested',
        cancelFeedback: null,
        externalSubscriptionId: 'sub_canceled_123',
        externalCustomerId: 'cus_canceled_456',
        createdAt: $now,
        updatedAt: $now,
    );

    $subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => $id->equals($orgId)))
        ->once()
        ->andReturn($canceledSubscription);

    $paymentGateway->shouldReceive('createPortalSession')
        ->with(
            'cus_canceled_456',
            'https://app.example.com/reactivate',
        )
        ->once()
        ->andReturn([
            'portal_url' => 'https://billing.stripe.com/session/canceled_789',
        ]);

    $useCase = new CreatePortalSessionUseCase($subscriptionRepo, $paymentGateway);

    $result = $useCase->execute(new CreatePortalSessionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        returnUrl: 'https://app.example.com/reactivate',
    ));

    expect($result->portalUrl)->toBe('https://billing.stripe.com/session/canceled_789');
});
