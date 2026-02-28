<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Entities;

use App\Domain\Campaign\Events\ContentCreated;
use App\Domain\Campaign\Events\ContentDeleted;
use App\Domain\Campaign\Events\ContentUpdated;
use App\Domain\Campaign\Exceptions\InvalidStatusTransitionException;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Content
{
    /**
     * @param  string[]  $hashtags
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $campaignId,
        public Uuid $createdBy,
        public ?string $title,
        public ?string $body,
        public array $hashtags,
        public ContentStatus $status,
        public ?string $aiGenerationId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
        public ?DateTimeImmutable $purgeAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  string[]  $hashtags
     */
    public static function create(
        Uuid $organizationId,
        Uuid $campaignId,
        Uuid $createdBy,
        ?string $title = null,
        ?string $body = null,
        array $hashtags = [],
        ?string $aiGenerationId = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            campaignId: $campaignId,
            createdBy: $createdBy,
            title: $title,
            body: $body,
            hashtags: $hashtags,
            status: ContentStatus::Draft,
            aiGenerationId: $aiGenerationId,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
            purgeAt: null,
            domainEvents: [
                new ContentCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $createdBy,
                    campaignId: (string) $campaignId,
                ),
            ],
        );
    }

    /**
     * @param  string[]  $hashtags
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $campaignId,
        Uuid $createdBy,
        ?string $title,
        ?string $body,
        array $hashtags,
        ContentStatus $status,
        ?string $aiGenerationId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $purgeAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            campaignId: $campaignId,
            createdBy: $createdBy,
            title: $title,
            body: $body,
            hashtags: $hashtags,
            status: $status,
            aiGenerationId: $aiGenerationId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
            purgeAt: $purgeAt,
        );
    }

    /**
     * @param  string[]|null  $hashtags
     */
    public function update(
        ?string $title = null,
        ?string $body = null,
        ?array $hashtags = null,
    ): self {
        if (! $this->status->isEditable()) {
            throw new InvalidStatusTransitionException($this->status->value, 'editing');
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            campaignId: $this->campaignId,
            createdBy: $this->createdBy,
            title: $title ?? $this->title,
            body: $body ?? $this->body,
            hashtags: $hashtags ?? $this->hashtags,
            status: $this->status,
            aiGenerationId: $this->aiGenerationId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            domainEvents: [
                ...$this->domainEvents,
                new ContentUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->createdBy,
                ),
            ],
        );
    }

    public function transitionTo(ContentStatus $newStatus): self
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new InvalidStatusTransitionException($this->status->value, $newStatus->value);
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            campaignId: $this->campaignId,
            createdBy: $this->createdBy,
            title: $this->title,
            body: $this->body,
            hashtags: $this->hashtags,
            status: $newStatus,
            aiGenerationId: $this->aiGenerationId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function softDelete(int $graceDays = 30): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            campaignId: $this->campaignId,
            createdBy: $this->createdBy,
            title: $this->title,
            body: $this->body,
            hashtags: $this->hashtags,
            status: $this->status,
            aiGenerationId: $this->aiGenerationId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $now,
            purgeAt: $now->modify("+{$graceDays} days"),
            domainEvents: [
                ...$this->domainEvents,
                new ContentDeleted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->createdBy,
                ),
            ],
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
            campaignId: $this->campaignId,
            createdBy: $this->createdBy,
            title: $this->title,
            body: $this->body,
            hashtags: $this->hashtags,
            status: $this->status,
            aiGenerationId: $this->aiGenerationId,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: null,
            purgeAt: null,
            domainEvents: $this->domainEvents,
        );
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            campaignId: $this->campaignId,
            createdBy: $this->createdBy,
            title: $this->title,
            body: $this->body,
            hashtags: $this->hashtags,
            status: $this->status,
            aiGenerationId: $this->aiGenerationId,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
        );
    }
}
