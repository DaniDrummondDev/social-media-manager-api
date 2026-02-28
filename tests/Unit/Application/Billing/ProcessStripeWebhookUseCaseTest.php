<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\ProcessStripeWebhookInput;
use App\Application\Billing\Exceptions\StripeWebhookAlreadyProcessedException;
use App\Application\Billing\UseCases\ProcessStripeWebhookUseCase;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Repositories\InvoiceRepositoryInterface;
use App\Domain\Billing\Repositories\StripeWebhookEventRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;

beforeEach(function () {
    $container = Container::getInstance();
    $config = new ConfigRepository([
        'services' => [
            'stripe' => [
                'webhook_secret' => 'whsec_test',
            ],
        ],
    ]);
    $container->instance('config', $config);
});

it('processes customer.subscription.created event', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subId = Uuid::generate();
    $now = new DateTimeImmutable;

    $subscription = Subscription::reconstitute(
        id: $subId,
        organizationId: $orgId,
        planId: $planId,
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_stripe_123',
        externalCustomerId: 'cus_stripe_456',
        createdAt: $now,
        updatedAt: $now,
    );

    $stripeEvent = [
        'id' => 'evt_123',
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_stripe_123',
                'status' => 'active',
                'customer' => 'cus_stripe_456',
                'current_period_start' => time(),
                'current_period_end' => time() + 2592000,
                'items' => [
                    'data' => [
                        [
                            'price' => [
                                'recurring' => ['interval' => 'month'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $paymentGateway = mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldReceive('validateWebhookSignature')->once()->andReturn(true);
    $paymentGateway->shouldReceive('constructWebhookEvent')->once()->andReturn($stripeEvent);

    $webhookEventRepo = mock(StripeWebhookEventRepositoryInterface::class);
    $webhookEventRepo->shouldReceive('createIfNotExists')->with('evt_123', 'customer.subscription.created', $stripeEvent)->once()->andReturn(true);
    $webhookEventRepo->shouldReceive('markProcessed')->with('evt_123', null)->once();

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findByExternalId')->with('sub_stripe_123')->once()->andReturn($subscription);
    $subscriptionRepo->shouldReceive('update')->once();

    $invoiceRepo = mock(InvoiceRepositoryInterface::class);

    $useCase = new ProcessStripeWebhookUseCase(
        $paymentGateway,
        $webhookEventRepo,
        $subscriptionRepo,
        $invoiceRepo,
    );

    $useCase->execute(new ProcessStripeWebhookInput(
        payload: '{"test":"payload"}',
        signature: 'sig_test_123',
    ));
});

it('processes invoice.paid event', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subId = Uuid::generate();
    $now = new DateTimeImmutable;

    $subscription = Subscription::reconstitute(
        id: $subId,
        organizationId: $orgId,
        planId: $planId,
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_stripe_123',
        externalCustomerId: 'cus_stripe_456',
        createdAt: $now,
        updatedAt: $now,
    );

    $stripeEvent = [
        'id' => 'evt_invoice_paid_1',
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_stripe_789',
                'subscription' => 'sub_stripe_123',
                'amount_paid' => 4900,
                'currency' => 'brl',
                'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                'period_start' => time(),
                'period_end' => time() + 2592000,
            ],
        ],
    ];

    $paymentGateway = mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldReceive('validateWebhookSignature')->once()->andReturn(true);
    $paymentGateway->shouldReceive('constructWebhookEvent')->once()->andReturn($stripeEvent);

    $webhookEventRepo = mock(StripeWebhookEventRepositoryInterface::class);
    $webhookEventRepo->shouldReceive('createIfNotExists')->with('evt_invoice_paid_1', 'invoice.paid', $stripeEvent)->once()->andReturn(true);
    $webhookEventRepo->shouldReceive('markProcessed')->with('evt_invoice_paid_1', null)->once();

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findByExternalId')->with('sub_stripe_123')->once()->andReturn($subscription);

    $invoiceRepo = mock(InvoiceRepositoryInterface::class);
    $invoiceRepo->shouldReceive('findByExternalId')->with('in_stripe_789')->once()->andReturn(null);
    $invoiceRepo->shouldReceive('create')->once();

    $useCase = new ProcessStripeWebhookUseCase(
        $paymentGateway,
        $webhookEventRepo,
        $subscriptionRepo,
        $invoiceRepo,
    );

    $useCase->execute(new ProcessStripeWebhookInput(
        payload: '{"test":"invoice_paid"}',
        signature: 'sig_test_456',
    ));
});

it('skips already processed event (idempotency)', function () {
    $paymentGateway = mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldReceive('validateWebhookSignature')->once()->andReturn(true);
    $paymentGateway->shouldReceive('constructWebhookEvent')->once()->andReturn([
        'id' => 'evt_already_processed',
        'type' => 'customer.subscription.created',
        'data' => ['object' => []],
    ]);

    $webhookEventRepo = mock(StripeWebhookEventRepositoryInterface::class);
    $webhookEventRepo->shouldReceive('createIfNotExists')
        ->once()
        ->andReturn(false);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $invoiceRepo = mock(InvoiceRepositoryInterface::class);

    $useCase = new ProcessStripeWebhookUseCase(
        $paymentGateway,
        $webhookEventRepo,
        $subscriptionRepo,
        $invoiceRepo,
    );

    $useCase->execute(new ProcessStripeWebhookInput(
        payload: '{"test":"duplicate"}',
        signature: 'sig_test_789',
    ));
})->throws(StripeWebhookAlreadyProcessedException::class);

it('handles unknown event type gracefully', function () {
    $stripeEvent = [
        'id' => 'evt_unknown',
        'type' => 'charge.refunded',
        'data' => ['object' => []],
    ];

    $paymentGateway = mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldReceive('validateWebhookSignature')->once()->andReturn(true);
    $paymentGateway->shouldReceive('constructWebhookEvent')->once()->andReturn($stripeEvent);

    $webhookEventRepo = mock(StripeWebhookEventRepositoryInterface::class);
    $webhookEventRepo->shouldReceive('createIfNotExists')->with('evt_unknown', 'charge.refunded', $stripeEvent)->once()->andReturn(true);
    $webhookEventRepo->shouldReceive('markProcessed')->with('evt_unknown', null)->once();

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldNotReceive('findByExternalId');

    $invoiceRepo = mock(InvoiceRepositoryInterface::class);

    $useCase = new ProcessStripeWebhookUseCase(
        $paymentGateway,
        $webhookEventRepo,
        $subscriptionRepo,
        $invoiceRepo,
    );

    $useCase->execute(new ProcessStripeWebhookInput(
        payload: '{"test":"unknown_event"}',
        signature: 'sig_test_unknown',
    ));
});
