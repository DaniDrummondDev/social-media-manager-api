<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Repositories;

use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\CancelFeedback;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Models\SubscriptionModel;
use DateTimeImmutable;

final class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        private readonly SubscriptionModel $model,
    ) {}

    public function findById(Uuid $id): ?Subscription
    {
        /** @var SubscriptionModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findActiveByOrganization(Uuid $organizationId): ?Subscription
    {
        /** @var SubscriptionModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function findByExternalId(string $externalSubscriptionId): ?Subscription
    {
        /** @var SubscriptionModel|null $record */
        $record = $this->model->newQuery()
            ->where('external_subscription_id', $externalSubscriptionId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function create(Subscription $subscription): void
    {
        $this->model->newQuery()->create($this->toArray($subscription));
    }

    public function update(Subscription $subscription): void
    {
        $this->model->newQuery()
            ->where('id', (string) $subscription->id)
            ->update($this->toArray($subscription));
    }

    /**
     * @return array<Subscription>
     */
    public function findExpiredPastDue(DateTimeImmutable $threshold): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SubscriptionModel> $records */
        $records = $this->model->newQuery()
            ->where('status', 'past_due')
            ->where('updated_at', '<', $threshold->format('Y-m-d H:i:s'))
            ->limit(100)
            ->get();

        return $records->map(fn (SubscriptionModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<Subscription>
     */
    public function findCanceledEndingBefore(DateTimeImmutable $date): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SubscriptionModel> $records */
        $records = $this->model->newQuery()
            ->where('cancel_at_period_end', true)
            ->whereIn('status', ['active', 'trialing'])
            ->where('current_period_end', '<', $date->format('Y-m-d H:i:s'))
            ->limit(100)
            ->get();

        return $records->map(fn (SubscriptionModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Subscription $subscription): array
    {
        return [
            'id' => (string) $subscription->id,
            'organization_id' => (string) $subscription->organizationId,
            'plan_id' => (string) $subscription->planId,
            'status' => $subscription->status->value,
            'billing_cycle' => $subscription->billingCycle->value,
            'current_period_start' => $subscription->currentPeriodStart->format('Y-m-d H:i:s'),
            'current_period_end' => $subscription->currentPeriodEnd->format('Y-m-d H:i:s'),
            'trial_ends_at' => $subscription->trialEndsAt?->format('Y-m-d H:i:s'),
            'canceled_at' => $subscription->canceledAt?->format('Y-m-d H:i:s'),
            'cancel_at_period_end' => $subscription->cancelAtPeriodEnd,
            'cancel_reason' => $subscription->cancelReason,
            'cancel_feedback' => $subscription->cancelFeedback?->value,
            'external_subscription_id' => $subscription->externalSubscriptionId,
            'external_customer_id' => $subscription->externalCustomerId,
        ];
    }

    private function toDomain(SubscriptionModel $model): Subscription
    {
        $trialEndsAt = $model->getAttribute('trial_ends_at');
        $canceledAt = $model->getAttribute('canceled_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $periodStart = $model->getAttribute('current_period_start');
        $periodEnd = $model->getAttribute('current_period_end');
        $cancelFeedback = $model->getAttribute('cancel_feedback');

        return Subscription::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            planId: Uuid::fromString($model->getAttribute('plan_id')),
            status: SubscriptionStatus::from($model->getAttribute('status')),
            billingCycle: BillingCycle::from($model->getAttribute('billing_cycle')),
            currentPeriodStart: new DateTimeImmutable($periodStart->format('Y-m-d H:i:s')),
            currentPeriodEnd: new DateTimeImmutable($periodEnd->format('Y-m-d H:i:s')),
            trialEndsAt: $trialEndsAt ? new DateTimeImmutable($trialEndsAt->format('Y-m-d H:i:s')) : null,
            canceledAt: $canceledAt ? new DateTimeImmutable($canceledAt->format('Y-m-d H:i:s')) : null,
            cancelAtPeriodEnd: (bool) $model->getAttribute('cancel_at_period_end'),
            cancelReason: $model->getAttribute('cancel_reason'),
            cancelFeedback: $cancelFeedback !== null ? CancelFeedback::from($cancelFeedback) : null,
            externalSubscriptionId: $model->getAttribute('external_subscription_id'),
            externalCustomerId: $model->getAttribute('external_customer_id'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
