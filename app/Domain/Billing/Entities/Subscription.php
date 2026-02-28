<?php

declare(strict_types=1);

namespace App\Domain\Billing\Entities;

use App\Domain\Billing\Events\PaymentFailed;
use App\Domain\Billing\Events\SubscriptionCanceled;
use App\Domain\Billing\Events\SubscriptionCreated;
use App\Domain\Billing\Events\SubscriptionExpired;
use App\Domain\Billing\Events\SubscriptionReactivated;
use App\Domain\Billing\Events\SubscriptionUpgraded;
use App\Domain\Billing\Exceptions\InvalidSubscriptionTransitionException;
use App\Domain\Billing\Exceptions\SubscriptionAlreadyCanceledException;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\CancelFeedback;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Subscription
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $planId,
        public SubscriptionStatus $status,
        public BillingCycle $billingCycle,
        public DateTimeImmutable $currentPeriodStart,
        public DateTimeImmutable $currentPeriodEnd,
        public ?DateTimeImmutable $trialEndsAt,
        public ?DateTimeImmutable $canceledAt,
        public bool $cancelAtPeriodEnd,
        public ?string $cancelReason,
        public ?CancelFeedback $cancelFeedback,
        public ?string $externalSubscriptionId,
        public ?string $externalCustomerId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function createFree(
        Uuid $organizationId,
        Uuid $freePlanId,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $periodStart = new DateTimeImmutable('first day of this month midnight');
        $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

        return new self(
            id: $id,
            organizationId: $organizationId,
            planId: $freePlanId,
            status: SubscriptionStatus::Active,
            billingCycle: BillingCycle::Monthly,
            currentPeriodStart: $periodStart,
            currentPeriodEnd: $periodEnd,
            trialEndsAt: null,
            canceledAt: null,
            cancelAtPeriodEnd: false,
            cancelReason: null,
            cancelFeedback: null,
            externalSubscriptionId: null,
            externalCustomerId: null,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new SubscriptionCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    planId: (string) $freePlanId,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $planId,
        SubscriptionStatus $status,
        BillingCycle $billingCycle,
        DateTimeImmutable $currentPeriodStart,
        DateTimeImmutable $currentPeriodEnd,
        ?DateTimeImmutable $trialEndsAt,
        ?DateTimeImmutable $canceledAt,
        bool $cancelAtPeriodEnd,
        ?string $cancelReason,
        ?CancelFeedback $cancelFeedback,
        ?string $externalSubscriptionId,
        ?string $externalCustomerId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            planId: $planId,
            status: $status,
            billingCycle: $billingCycle,
            currentPeriodStart: $currentPeriodStart,
            currentPeriodEnd: $currentPeriodEnd,
            trialEndsAt: $trialEndsAt,
            canceledAt: $canceledAt,
            cancelAtPeriodEnd: $cancelAtPeriodEnd,
            cancelReason: $cancelReason,
            cancelFeedback: $cancelFeedback,
            externalSubscriptionId: $externalSubscriptionId,
            externalCustomerId: $externalCustomerId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function cancel(?string $reason = null, ?CancelFeedback $feedback = null): self
    {
        if (! $this->canCancel()) {
            throw new SubscriptionAlreadyCanceledException;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            planId: $this->planId,
            status: $this->status,
            billingCycle: $this->billingCycle,
            currentPeriodStart: $this->currentPeriodStart,
            currentPeriodEnd: $this->currentPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: $now,
            cancelAtPeriodEnd: true,
            cancelReason: $reason,
            cancelFeedback: $feedback,
            externalSubscriptionId: $this->externalSubscriptionId,
            externalCustomerId: $this->externalCustomerId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new SubscriptionCanceled(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: 'system',
                    cancelAtPeriodEnd: true,
                ),
            ],
        );
    }

    public function reactivate(): self
    {
        if (! $this->canReactivate()) {
            throw new InvalidSubscriptionTransitionException(
                "Não é possível reativar assinatura com status '{$this->status->value}' e cancelAtPeriodEnd={$this->cancelAtPeriodEnd}.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            planId: $this->planId,
            status: $this->status,
            billingCycle: $this->billingCycle,
            currentPeriodStart: $this->currentPeriodStart,
            currentPeriodEnd: $this->currentPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: null,
            cancelAtPeriodEnd: false,
            cancelReason: null,
            cancelFeedback: null,
            externalSubscriptionId: $this->externalSubscriptionId,
            externalCustomerId: $this->externalCustomerId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new SubscriptionReactivated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: 'system',
                ),
            ],
        );
    }

    public function markPastDue(): self
    {
        if ($this->status !== SubscriptionStatus::Active) {
            throw new InvalidSubscriptionTransitionException(
                "Não é possível marcar como past_due a partir do status '{$this->status->value}'.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            planId: $this->planId,
            status: SubscriptionStatus::PastDue,
            billingCycle: $this->billingCycle,
            currentPeriodStart: $this->currentPeriodStart,
            currentPeriodEnd: $this->currentPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: $this->canceledAt,
            cancelAtPeriodEnd: $this->cancelAtPeriodEnd,
            cancelReason: $this->cancelReason,
            cancelFeedback: $this->cancelFeedback,
            externalSubscriptionId: $this->externalSubscriptionId,
            externalCustomerId: $this->externalCustomerId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new PaymentFailed(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: 'system',
                ),
            ],
        );
    }

    public function activate(): self
    {
        if (! in_array($this->status, [SubscriptionStatus::Trialing, SubscriptionStatus::PastDue], true)) {
            throw new InvalidSubscriptionTransitionException(
                "Não é possível ativar a partir do status '{$this->status->value}'.",
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            planId: $this->planId,
            status: SubscriptionStatus::Active,
            billingCycle: $this->billingCycle,
            currentPeriodStart: $this->currentPeriodStart,
            currentPeriodEnd: $this->currentPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: $this->canceledAt,
            cancelAtPeriodEnd: $this->cancelAtPeriodEnd,
            cancelReason: $this->cancelReason,
            cancelFeedback: $this->cancelFeedback,
            externalSubscriptionId: $this->externalSubscriptionId,
            externalCustomerId: $this->externalCustomerId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function expire(): self
    {
        if (! in_array($this->status, [SubscriptionStatus::PastDue, SubscriptionStatus::Canceled, SubscriptionStatus::Active], true)) {
            throw new InvalidSubscriptionTransitionException(
                "Não é possível expirar a partir do status '{$this->status->value}'.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            planId: $this->planId,
            status: SubscriptionStatus::Expired,
            billingCycle: $this->billingCycle,
            currentPeriodStart: $this->currentPeriodStart,
            currentPeriodEnd: $this->currentPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: $this->canceledAt,
            cancelAtPeriodEnd: $this->cancelAtPeriodEnd,
            cancelReason: $this->cancelReason,
            cancelFeedback: $this->cancelFeedback,
            externalSubscriptionId: $this->externalSubscriptionId,
            externalCustomerId: $this->externalCustomerId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new SubscriptionExpired(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: 'system',
                ),
            ],
        );
    }

    public function upgrade(Uuid $newPlanId): self
    {
        if (! in_array($this->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)) {
            throw new InvalidSubscriptionTransitionException(
                "Não é possível fazer upgrade com status '{$this->status->value}'.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            planId: $newPlanId,
            status: $this->status,
            billingCycle: $this->billingCycle,
            currentPeriodStart: $this->currentPeriodStart,
            currentPeriodEnd: $this->currentPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: null,
            cancelAtPeriodEnd: false,
            cancelReason: null,
            cancelFeedback: null,
            externalSubscriptionId: $this->externalSubscriptionId,
            externalCustomerId: $this->externalCustomerId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new SubscriptionUpgraded(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: 'system',
                    oldPlanId: (string) $this->planId,
                    newPlanId: (string) $newPlanId,
                ),
            ],
        );
    }

    public function updateFromWebhook(
        SubscriptionStatus $status,
        Uuid $planId,
        BillingCycle $cycle,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        ?string $externalSubscriptionId,
        ?string $externalCustomerId,
    ): self {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            planId: $planId,
            status: $status,
            billingCycle: $cycle,
            currentPeriodStart: $periodStart,
            currentPeriodEnd: $periodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: $this->canceledAt,
            cancelAtPeriodEnd: $this->cancelAtPeriodEnd,
            cancelReason: $this->cancelReason,
            cancelFeedback: $this->cancelFeedback,
            externalSubscriptionId: $externalSubscriptionId ?? $this->externalSubscriptionId,
            externalCustomerId: $externalCustomerId ?? $this->externalCustomerId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)
            && ! $this->cancelAtPeriodEnd;
    }

    public function canReactivate(): bool
    {
        return $this->cancelAtPeriodEnd
            && in_array($this->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true);
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array
    {
        return $this->domainEvents;
    }
}
