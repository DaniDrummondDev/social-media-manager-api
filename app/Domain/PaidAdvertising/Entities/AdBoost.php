<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Entities;

use App\Domain\PaidAdvertising\Events\BoostActivated;
use App\Domain\PaidAdvertising\Events\BoostCancelled;
use App\Domain\PaidAdvertising\Events\BoostCompleted;
use App\Domain\PaidAdvertising\Events\BoostCreated;
use App\Domain\PaidAdvertising\Events\BoostRejected;
use App\Domain\PaidAdvertising\Exceptions\BoostNotAllowedException;
use App\Domain\PaidAdvertising\Exceptions\InvalidAdStatusTransitionException;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AdBoost
{
    /**
     * @param  array<string, string>|null  $externalIds  Map of campaign_id, adset_id, ad_id on the platform
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $scheduledPostId,
        public Uuid $adAccountId,
        public Uuid $audienceId,
        public AdBudget $budget,
        public int $durationDays,
        public AdObjective $objective,
        public AdStatus $status,
        public ?array $externalIds,
        public ?string $rejectionReason,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt,
        public Uuid $createdBy,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $scheduledPostId,
        Uuid $adAccountId,
        Uuid $audienceId,
        AdBudget $budget,
        int $durationDays,
        AdObjective $objective,
        Uuid $createdBy,
    ): self {
        if ($durationDays < 1) {
            throw new BoostNotAllowedException('Duracao do boost deve ser de pelo menos 1 dia.');
        }

        if ($budget->isZero()) {
            throw new BoostNotAllowedException('Orcamento do boost nao pode ser zero.');
        }

        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            scheduledPostId: $scheduledPostId,
            adAccountId: $adAccountId,
            audienceId: $audienceId,
            budget: $budget,
            durationDays: $durationDays,
            objective: $objective,
            status: AdStatus::Draft,
            externalIds: null,
            rejectionReason: null,
            startedAt: null,
            completedAt: null,
            createdBy: $createdBy,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new BoostCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $createdBy,
                    scheduledPostId: (string) $scheduledPostId,
                    audienceId: (string) $audienceId,
                    objective: $objective->value,
                    budgetCents: $budget->amountCents,
                    currency: $budget->currency,
                ),
            ],
        );
    }

    /**
     * @param  array<string, string>|null  $externalIds
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $scheduledPostId,
        Uuid $adAccountId,
        Uuid $audienceId,
        AdBudget $budget,
        int $durationDays,
        AdObjective $objective,
        AdStatus $status,
        ?array $externalIds,
        ?string $rejectionReason,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt,
        Uuid $createdBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            scheduledPostId: $scheduledPostId,
            adAccountId: $adAccountId,
            audienceId: $audienceId,
            budget: $budget,
            durationDays: $durationDays,
            objective: $objective,
            status: $status,
            externalIds: $externalIds,
            rejectionReason: $rejectionReason,
            startedAt: $startedAt,
            completedAt: $completedAt,
            createdBy: $createdBy,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function submitForReview(): self
    {
        $this->assertTransition(AdStatus::PendingReview);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: AdStatus::PendingReview,
            externalIds: $this->externalIds,
            rejectionReason: $this->rejectionReason,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    /**
     * @param  array<string, string>  $externalIds
     */
    public function activate(array $externalIds, string $userId): self
    {
        $this->assertTransition(AdStatus::Active);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: AdStatus::Active,
            externalIds: $externalIds,
            rejectionReason: $this->rejectionReason,
            startedAt: new DateTimeImmutable,
            completedAt: $this->completedAt,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                ...$this->domainEvents,
                new BoostActivated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    externalCampaignId: $externalIds['campaign_id'] ?? '',
                ),
            ],
        );
    }

    public function pause(string $userId): self
    {
        $this->assertTransition(AdStatus::Paused);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: AdStatus::Paused,
            externalIds: $this->externalIds,
            rejectionReason: $this->rejectionReason,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function resume(string $userId): self
    {
        $this->assertTransition(AdStatus::Active);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: AdStatus::Active,
            externalIds: $this->externalIds,
            rejectionReason: $this->rejectionReason,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function complete(int $totalSpendCents, string $userId): self
    {
        $this->assertTransition(AdStatus::Completed);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: AdStatus::Completed,
            externalIds: $this->externalIds,
            rejectionReason: $this->rejectionReason,
            startedAt: $this->startedAt,
            completedAt: new DateTimeImmutable,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                ...$this->domainEvents,
                new BoostCompleted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    totalSpendCents: $totalSpendCents,
                ),
            ],
        );
    }

    public function reject(string $reason, string $userId): self
    {
        $this->assertTransition(AdStatus::Rejected);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: AdStatus::Rejected,
            externalIds: $this->externalIds,
            rejectionReason: $reason,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                ...$this->domainEvents,
                new BoostRejected(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    rejectionReason: $reason,
                ),
            ],
        );
    }

    public function cancel(string $userId): self
    {
        if (! $this->status->canBeCancelled()) {
            throw new BoostNotAllowedException(
                "Boost no status '{$this->status->value}' nao pode ser cancelado.",
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: AdStatus::Cancelled,
            externalIds: $this->externalIds,
            rejectionReason: $this->rejectionReason,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                ...$this->domainEvents,
                new BoostCancelled(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                ),
            ],
        );
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            scheduledPostId: $this->scheduledPostId,
            adAccountId: $this->adAccountId,
            audienceId: $this->audienceId,
            budget: $this->budget,
            durationDays: $this->durationDays,
            objective: $this->objective,
            status: $this->status,
            externalIds: $this->externalIds,
            rejectionReason: $this->rejectionReason,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private function assertTransition(AdStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidAdStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }
}
