<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Entities;

use App\Domain\ClientFinance\Events\CostAllocated;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\ClientFinance\ValueObjects\ResourceType;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class CostAllocation
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $clientId,
        public Uuid $organizationId,
        public ResourceType $resourceType,
        public ?Uuid $resourceId,
        public string $description,
        public int $costCents,
        public Currency $currency,
        public DateTimeImmutable $allocatedAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $clientId,
        Uuid $organizationId,
        ResourceType $resourceType,
        ?Uuid $resourceId,
        string $description,
        int $costCents,
        Currency $currency,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            clientId: $clientId,
            organizationId: $organizationId,
            resourceType: $resourceType,
            resourceId: $resourceId,
            description: $description,
            costCents: $costCents,
            currency: $currency,
            allocatedAt: $now,
            createdAt: $now,
            domainEvents: [
                new CostAllocated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    clientId: (string) $clientId,
                    costCents: $costCents,
                    resourceType: $resourceType->value,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $clientId,
        Uuid $organizationId,
        ResourceType $resourceType,
        ?Uuid $resourceId,
        string $description,
        int $costCents,
        Currency $currency,
        DateTimeImmutable $allocatedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            clientId: $clientId,
            organizationId: $organizationId,
            resourceType: $resourceType,
            resourceId: $resourceId,
            description: $description,
            costCents: $costCents,
            currency: $currency,
            allocatedAt: $allocatedAt,
            createdAt: $createdAt,
        );
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array
    {
        return $this->domainEvents;
    }
}
