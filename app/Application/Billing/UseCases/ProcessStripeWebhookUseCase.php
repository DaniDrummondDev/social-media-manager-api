<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\ProcessStripeWebhookInput;
use App\Application\Billing\Exceptions\StripeWebhookAlreadyProcessedException;
use App\Application\Billing\Exceptions\StripeWebhookInvalidSignatureException;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Entities\Invoice;
use App\Domain\Billing\Repositories\InvoiceRepositoryInterface;
use App\Domain\Billing\Repositories\StripeWebhookEventRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\InvoiceStatus;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use DateTimeImmutable;

final class ProcessStripeWebhookUseCase
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly StripeWebhookEventRepositoryInterface $webhookEventRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(ProcessStripeWebhookInput $input): void
    {
        $webhookSecret = config('services.stripe.webhook_secret', 'whsec_test');

        $isValid = $this->paymentGateway->validateWebhookSignature(
            $input->payload,
            $input->signature,
            $webhookSecret,
        );

        if (! $isValid) {
            throw new StripeWebhookInvalidSignatureException;
        }

        $event = $this->paymentGateway->constructWebhookEvent(
            $input->payload,
            $input->signature,
            $webhookSecret,
        );

        $stripeEventId = $event['id'] ?? '';
        $eventType = $event['type'] ?? '';

        if ($this->webhookEventRepository->existsByStripeEventId($stripeEventId)) {
            throw new StripeWebhookAlreadyProcessedException;
        }

        $this->webhookEventRepository->create($stripeEventId, $eventType, $event);

        $errorMessage = null;
        try {
            match ($eventType) {
                'customer.subscription.created',
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'invoice.paid' => $this->handleInvoicePaid($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                'customer.subscription.trial_will_end' => $this->handleTrialWillEnd($event),
                default => null,
            };
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        $this->webhookEventRepository->markProcessed($stripeEventId, $errorMessage);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleSubscriptionUpdated(array $event): void
    {
        $data = $event['data']['object'] ?? [];
        $externalId = $data['id'] ?? '';

        $subscription = $this->subscriptionRepository->findByExternalId($externalId);
        if ($subscription === null) {
            return;
        }

        $status = $this->mapStripeStatus($data['status'] ?? 'active');
        $interval = $data['items']['data'][0]['price']['recurring']['interval'] ?? 'month';
        $cycle = $interval === 'year' ? BillingCycle::Yearly : BillingCycle::Monthly;

        $subscription = $subscription->updateFromWebhook(
            status: $status,
            planId: $subscription->planId,
            cycle: $cycle,
            periodStart: new DateTimeImmutable('@'.($data['current_period_start'] ?? time())),
            periodEnd: new DateTimeImmutable('@'.($data['current_period_end'] ?? time())),
            externalSubscriptionId: $externalId,
            externalCustomerId: $data['customer'] ?? null,
        );

        $this->subscriptionRepository->update($subscription);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleSubscriptionDeleted(array $event): void
    {
        $data = $event['data']['object'] ?? [];
        $externalId = $data['id'] ?? '';

        $subscription = $this->subscriptionRepository->findByExternalId($externalId);
        if ($subscription === null) {
            return;
        }

        $subscription = $subscription->expire();
        $this->subscriptionRepository->update($subscription);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleInvoicePaid(array $event): void
    {
        $data = $event['data']['object'] ?? [];
        $externalInvoiceId = $data['id'] ?? '';

        $existing = $this->invoiceRepository->findByExternalId($externalInvoiceId);
        if ($existing !== null) {
            return;
        }

        $externalSubId = $data['subscription'] ?? '';
        $subscription = $this->subscriptionRepository->findByExternalId($externalSubId);
        if ($subscription === null) {
            return;
        }

        $invoice = Invoice::create(
            organizationId: $subscription->organizationId,
            subscriptionId: $subscription->id,
            externalInvoiceId: $externalInvoiceId,
            amount: Money::fromCents((int) ($data['amount_paid'] ?? 0), strtoupper($data['currency'] ?? 'brl')),
            status: InvoiceStatus::Paid,
            invoiceUrl: $data['hosted_invoice_url'] ?? null,
            periodStart: new DateTimeImmutable('@'.($data['period_start'] ?? time())),
            periodEnd: new DateTimeImmutable('@'.($data['period_end'] ?? time())),
            paidAt: new DateTimeImmutable,
        );

        $this->invoiceRepository->create($invoice);

        if ($subscription->status === SubscriptionStatus::PastDue) {
            $subscription = $subscription->activate();
            $this->subscriptionRepository->update($subscription);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleInvoicePaymentFailed(array $event): void
    {
        $data = $event['data']['object'] ?? [];
        $externalSubId = $data['subscription'] ?? '';

        $subscription = $this->subscriptionRepository->findByExternalId($externalSubId);
        if ($subscription === null) {
            return;
        }

        if ($subscription->status === SubscriptionStatus::Active) {
            $subscription = $subscription->markPastDue();
            $this->subscriptionRepository->update($subscription);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleTrialWillEnd(array $event): void
    {
        // For now, just record the event. Notification logic will be added later.
    }

    private function mapStripeStatus(string $stripeStatus): SubscriptionStatus
    {
        return match ($stripeStatus) {
            'trialing' => SubscriptionStatus::Trialing,
            'active' => SubscriptionStatus::Active,
            'past_due' => SubscriptionStatus::PastDue,
            'canceled' => SubscriptionStatus::Canceled,
            'unpaid' => SubscriptionStatus::Expired,
            default => SubscriptionStatus::Active,
        };
    }
}
