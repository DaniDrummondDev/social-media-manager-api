<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Entities;

use App\Domain\PlatformAdmin\Events\SystemConfigUpdated;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class SystemConfig
{
    /**
     * @param  array<int, \App\Domain\Shared\Events\DomainEvent>  $domainEvents
     */
    public function __construct(
        public string $key,
        public mixed $value,
        public string $valueType,
        public ?string $description,
        public bool $isSecret,
        public ?Uuid $updatedBy,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function reconstitute(
        string $key,
        mixed $value,
        string $valueType,
        ?string $description,
        bool $isSecret,
        ?Uuid $updatedBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            key: $key,
            value: $value,
            valueType: $valueType,
            description: $description,
            isSecret: $isSecret,
            updatedBy: $updatedBy,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function updateValue(mixed $newValue, Uuid $adminId): self
    {
        $oldValue = $this->value;

        return new self(
            key: $this->key,
            value: $newValue,
            valueType: $this->valueType,
            description: $this->description,
            isSecret: $this->isSecret,
            updatedBy: $adminId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new SystemConfigUpdated(
                    aggregateId: $this->key,
                    organizationId: '',
                    userId: (string) $adminId,
                    configKey: $this->key,
                    oldValue: $oldValue,
                    newValue: $newValue,
                ),
            ],
        );
    }

    /**
     * @return array<int, \App\Domain\Shared\Events\DomainEvent>
     */
    public function releaseEvents(): array
    {
        return $this->domainEvents;
    }
}
