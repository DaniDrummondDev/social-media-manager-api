<?php

declare(strict_types=1);

namespace App\Domain\Billing\Entities;

use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class UsageRecord
{
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public UsageResourceType $resourceType,
        public int $quantity,
        public DateTimeImmutable $periodStart,
        public DateTimeImmutable $periodEnd,
        public DateTimeImmutable $recordedAt,
    ) {}

    public static function create(
        Uuid $organizationId,
        UsageResourceType $resourceType,
        int $quantity,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
    ): self {
        return new self(
            id: Uuid::generate(),
            organizationId: $organizationId,
            resourceType: $resourceType,
            quantity: $quantity,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            recordedAt: new DateTimeImmutable,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        UsageResourceType $resourceType,
        int $quantity,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        DateTimeImmutable $recordedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            resourceType: $resourceType,
            quantity: $quantity,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            recordedAt: $recordedAt,
        );
    }

    public function increment(int $amount = 1): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            resourceType: $this->resourceType,
            quantity: $this->quantity + $amount,
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            recordedAt: new DateTimeImmutable,
        );
    }

    public function setQuantity(int $quantity): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            resourceType: $this->resourceType,
            quantity: $quantity,
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            recordedAt: new DateTimeImmutable,
        );
    }
}
