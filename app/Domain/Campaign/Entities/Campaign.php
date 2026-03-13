<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Entities;

use App\Domain\Campaign\Events\CampaignCreated;
use App\Domain\Campaign\Exceptions\InvalidStatusTransitionException;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Campaign
{
    /**
     * @param  string[]  $tags
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $createdBy,
        public string $name,
        public ?string $description,
        public ?DateTimeImmutable $startsAt,
        public ?DateTimeImmutable $endsAt,
        public CampaignStatus $status,
        public array $tags,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
        public ?DateTimeImmutable $purgeAt,
        public ?CampaignBrief $brief = null,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  string[]  $tags
     */
    public static function create(
        Uuid $organizationId,
        Uuid $createdBy,
        string $name,
        ?string $description = null,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        array $tags = [],
        ?CampaignBrief $brief = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            createdBy: $createdBy,
            name: $name,
            description: $description,
            startsAt: $startsAt,
            endsAt: $endsAt,
            status: CampaignStatus::Draft,
            tags: $tags,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
            purgeAt: null,
            brief: $brief,
            domainEvents: [
                new CampaignCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $createdBy,
                    name: $name,
                ),
            ],
        );
    }

    /**
     * @param  string[]  $tags
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $createdBy,
        string $name,
        ?string $description,
        ?DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
        CampaignStatus $status,
        array $tags,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $purgeAt,
        ?CampaignBrief $brief = null,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            createdBy: $createdBy,
            name: $name,
            description: $description,
            startsAt: $startsAt,
            endsAt: $endsAt,
            status: $status,
            tags: $tags,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
            purgeAt: $purgeAt,
            brief: $brief,
        );
    }

    /**
     * @param  string[]|null  $tags
     */
    public function update(
        ?string $name = null,
        ?string $description = null,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        ?array $tags = null,
        ?CampaignStatus $status = null,
        ?CampaignBrief $brief = null,
    ): self {
        if ($status !== null && ! $this->status->canTransitionTo($status)) {
            throw new InvalidStatusTransitionException($this->status->value, $status->value);
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            createdBy: $this->createdBy,
            name: $name ?? $this->name,
            description: $description ?? $this->description,
            startsAt: $startsAt ?? $this->startsAt,
            endsAt: $endsAt ?? $this->endsAt,
            status: $status ?? $this->status,
            tags: $tags ?? $this->tags,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            brief: $brief ?? $this->brief,
            domainEvents: $this->domainEvents,
        );
    }

    public function softDelete(int $graceDays = 30): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            createdBy: $this->createdBy,
            name: $this->name,
            description: $this->description,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            status: $this->status,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $now,
            purgeAt: $now->modify("+{$graceDays} days"),
            brief: $this->brief,
            domainEvents: $this->domainEvents,
        );
    }

    public function restore(): self
    {
        if ($this->deletedAt === null) {
            return $this;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            createdBy: $this->createdBy,
            name: $this->name,
            description: $this->description,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            status: $this->status,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: null,
            purgeAt: null,
            brief: $this->brief,
            domainEvents: $this->domainEvents,
        );
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable() && $this->deletedAt === null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isPurgeable(): bool
    {
        return $this->deletedAt !== null
            && $this->purgeAt !== null
            && new DateTimeImmutable >= $this->purgeAt;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            createdBy: $this->createdBy,
            name: $this->name,
            description: $this->description,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            status: $this->status,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            brief: $this->brief,
        );
    }
}
