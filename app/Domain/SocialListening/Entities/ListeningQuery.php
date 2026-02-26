<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Entities;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Events\ListeningQueryCreated;
use App\Domain\SocialListening\Events\ListeningQueryPaused;
use App\Domain\SocialListening\Events\ListeningQueryResumed;
use App\Domain\SocialListening\Exceptions\InvalidQueryTransitionException;
use App\Domain\SocialListening\Exceptions\QueryAlreadyDeletedException;
use App\Domain\SocialListening\ValueObjects\QueryStatus;
use App\Domain\SocialListening\ValueObjects\QueryType;
use DateTimeImmutable;

final readonly class ListeningQuery
{
    /**
     * @param  array<string>  $platforms
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $name,
        public QueryType $type,
        public string $value,
        public array $platforms,
        public QueryStatus $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>  $platforms
     */
    public static function create(
        Uuid $organizationId,
        string $name,
        QueryType $type,
        string $value,
        array $platforms,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            type: $type,
            value: $value,
            platforms: $platforms,
            status: QueryStatus::Active,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new ListeningQueryCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    queryType: $type->value,
                    value: $value,
                ),
            ],
        );
    }

    /**
     * @param  array<string>  $platforms
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $name,
        QueryType $type,
        string $value,
        array $platforms,
        QueryStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            type: $type,
            value: $value,
            platforms: $platforms,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function pause(string $userId): self
    {
        if (! $this->status->canTransitionTo(QueryStatus::Paused)) {
            throw new InvalidQueryTransitionException(
                "Não é possível pausar query com status '{$this->status->value}'.",
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            type: $this->type,
            value: $this->value,
            platforms: $this->platforms,
            status: QueryStatus::Paused,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new ListeningQueryPaused(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                ),
            ],
        );
    }

    public function resume(string $userId): self
    {
        if (! $this->status->canTransitionTo(QueryStatus::Active)) {
            throw new InvalidQueryTransitionException(
                "Não é possível retomar query com status '{$this->status->value}'.",
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            type: $this->type,
            value: $this->value,
            platforms: $this->platforms,
            status: QueryStatus::Active,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new ListeningQueryResumed(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                ),
            ],
        );
    }

    public function markDeleted(): self
    {
        if (! $this->status->canTransitionTo(QueryStatus::Deleted)) {
            throw new QueryAlreadyDeletedException;
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            type: $this->type,
            value: $this->value,
            platforms: $this->platforms,
            status: QueryStatus::Deleted,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<string>|null  $platforms
     */
    public function updateDetails(?string $name, ?string $value, ?array $platforms): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $name ?? $this->name,
            type: $this->type,
            value: $value ?? $this->value,
            platforms: $platforms ?? $this->platforms,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function isActive(): bool
    {
        return $this->status === QueryStatus::Active;
    }
}
