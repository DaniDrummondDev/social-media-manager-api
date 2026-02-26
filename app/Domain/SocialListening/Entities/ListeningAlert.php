<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Entities;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Events\ListeningAlertTriggered;
use App\Domain\SocialListening\Exceptions\AlertCooldownActiveException;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;
use DateTimeImmutable;

final readonly class ListeningAlert
{
    /**
     * @param  array<string>  $queryIds
     * @param  array<NotificationChannel>  $channels
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $name,
        public array $queryIds,
        public AlertCondition $condition,
        public array $channels,
        public int $cooldownMinutes,
        public bool $isActive,
        public ?DateTimeImmutable $lastTriggeredAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>  $queryIds
     * @param  array<NotificationChannel>  $channels
     */
    public static function create(
        Uuid $organizationId,
        string $name,
        array $queryIds,
        AlertCondition $condition,
        array $channels,
        int $cooldownMinutes,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            queryIds: $queryIds,
            condition: $condition,
            channels: $channels,
            cooldownMinutes: $cooldownMinutes,
            isActive: true,
            lastTriggeredAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param  array<string>  $queryIds
     * @param  array<NotificationChannel>  $channels
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $name,
        array $queryIds,
        AlertCondition $condition,
        array $channels,
        int $cooldownMinutes,
        bool $isActive,
        ?DateTimeImmutable $lastTriggeredAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            queryIds: $queryIds,
            condition: $condition,
            channels: $channels,
            cooldownMinutes: $cooldownMinutes,
            isActive: $isActive,
            lastTriggeredAt: $lastTriggeredAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            queryIds: $this->queryIds,
            condition: $this->condition,
            channels: $this->channels,
            cooldownMinutes: $this->cooldownMinutes,
            isActive: true,
            lastTriggeredAt: $this->lastTriggeredAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            queryIds: $this->queryIds,
            condition: $this->condition,
            channels: $this->channels,
            cooldownMinutes: $this->cooldownMinutes,
            isActive: false,
            lastTriggeredAt: $this->lastTriggeredAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<string>|null  $queryIds
     * @param  array<NotificationChannel>|null  $channels
     */
    public function updateDetails(
        ?string $name,
        ?AlertCondition $condition,
        ?array $queryIds,
        ?array $channels,
        ?int $cooldownMinutes,
    ): self {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $name ?? $this->name,
            queryIds: $queryIds ?? $this->queryIds,
            condition: $condition ?? $this->condition,
            channels: $channels ?? $this->channels,
            cooldownMinutes: $cooldownMinutes ?? $this->cooldownMinutes,
            isActive: $this->isActive,
            lastTriggeredAt: $this->lastTriggeredAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function canTrigger(): bool
    {
        if (! $this->isActive) {
            return false;
        }

        if ($this->lastTriggeredAt === null) {
            return true;
        }

        $cooldownEnd = $this->lastTriggeredAt->modify("+{$this->cooldownMinutes} minutes");

        return $cooldownEnd <= new DateTimeImmutable;
    }

    public function markTriggered(string $queryId): self
    {
        if (! $this->canTrigger()) {
            throw new AlertCooldownActiveException;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            queryIds: $this->queryIds,
            condition: $this->condition,
            channels: $this->channels,
            cooldownMinutes: $this->cooldownMinutes,
            isActive: $this->isActive,
            lastTriggeredAt: $now,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new ListeningAlertTriggered(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: 'system',
                    conditionType: $this->condition->type->value,
                    queryId: $queryId,
                ),
            ],
        );
    }
}
