<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Entities;

use App\Domain\ClientFinance\Events\ClientArchived;
use App\Domain\ClientFinance\Events\ClientCreated;
use App\Domain\ClientFinance\Events\ClientUpdated;
use App\Domain\ClientFinance\Exceptions\ClientAlreadyArchivedException;
use App\Domain\ClientFinance\ValueObjects\Address;
use App\Domain\ClientFinance\ValueObjects\ClientStatus;
use App\Domain\ClientFinance\ValueObjects\TaxId;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Client
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $companyName,
        public ?TaxId $taxId,
        public ?Address $billingAddress,
        public ?string $notes,
        public ClientStatus $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
        public ?DateTimeImmutable $purgeAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        string $name,
        string $userId,
        ?string $email = null,
        ?string $phone = null,
        ?string $companyName = null,
        ?TaxId $taxId = null,
        ?Address $billingAddress = null,
        ?string $notes = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            email: $email,
            phone: $phone,
            companyName: $companyName,
            taxId: $taxId,
            billingAddress: $billingAddress,
            notes: $notes,
            status: ClientStatus::Active,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
            purgeAt: null,
            domainEvents: [
                new ClientCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    clientName: $name,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $name,
        ?string $email,
        ?string $phone,
        ?string $companyName,
        ?TaxId $taxId,
        ?Address $billingAddress,
        ?string $notes,
        ClientStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $purgeAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            email: $email,
            phone: $phone,
            companyName: $companyName,
            taxId: $taxId,
            billingAddress: $billingAddress,
            notes: $notes,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
            purgeAt: $purgeAt,
        );
    }

    public function update(
        string $userId,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $companyName = null,
        ?TaxId $taxId = null,
        ?Address $billingAddress = null,
        ?string $notes = null,
        ?ClientStatus $status = null,
    ): self {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $name ?? $this->name,
            email: $email ?? $this->email,
            phone: $phone ?? $this->phone,
            companyName: $companyName ?? $this->companyName,
            taxId: $taxId ?? $this->taxId,
            billingAddress: $billingAddress ?? $this->billingAddress,
            notes: $notes ?? $this->notes,
            status: $status ?? $this->status,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            domainEvents: [
                new ClientUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                ),
            ],
        );
    }

    public function archive(string $userId, int $graceDays = 90): self
    {
        if ($this->status === ClientStatus::Archived) {
            throw new ClientAlreadyArchivedException;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            email: $this->email,
            phone: $this->phone,
            companyName: $this->companyName,
            taxId: $this->taxId,
            billingAddress: $this->billingAddress,
            notes: $this->notes,
            status: ClientStatus::Archived,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $now,
            purgeAt: $now->modify("+{$graceDays} days"),
            domainEvents: [
                new ClientArchived(
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

    public function isArchived(): bool
    {
        return $this->status === ClientStatus::Archived;
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array
    {
        return $this->domainEvents;
    }
}
