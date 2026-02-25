<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Entities;

use App\Domain\ClientFinance\Events\ContractCompleted;
use App\Domain\ClientFinance\Events\ContractCreated;
use App\Domain\ClientFinance\Exceptions\InvalidContractTransitionException;
use App\Domain\ClientFinance\ValueObjects\ContractStatus;
use App\Domain\ClientFinance\ValueObjects\ContractType;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class ClientContract
{
    /**
     * @param  array<string>  $socialAccountIds
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $clientId,
        public Uuid $organizationId,
        public string $name,
        public ContractType $type,
        public int $valueCents,
        public Currency $currency,
        public DateTimeImmutable $startsAt,
        public ?DateTimeImmutable $endsAt,
        public array $socialAccountIds,
        public ContractStatus $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>  $socialAccountIds
     */
    public static function create(
        Uuid $clientId,
        Uuid $organizationId,
        string $name,
        ContractType $type,
        int $valueCents,
        Currency $currency,
        DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
        array $socialAccountIds,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            clientId: $clientId,
            organizationId: $organizationId,
            name: $name,
            type: $type,
            valueCents: $valueCents,
            currency: $currency,
            startsAt: $startsAt,
            endsAt: $endsAt,
            socialAccountIds: $socialAccountIds,
            status: ContractStatus::Active,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new ContractCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    clientId: (string) $clientId,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $clientId,
        Uuid $organizationId,
        string $name,
        ContractType $type,
        int $valueCents,
        Currency $currency,
        DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
        array $socialAccountIds,
        ContractStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            clientId: $clientId,
            organizationId: $organizationId,
            name: $name,
            type: $type,
            valueCents: $valueCents,
            currency: $currency,
            startsAt: $startsAt,
            endsAt: $endsAt,
            socialAccountIds: $socialAccountIds,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param  array<string>|null  $socialAccountIds
     */
    public function update(
        ?string $name = null,
        ?int $valueCents = null,
        ?DateTimeImmutable $endsAt = null,
        ?array $socialAccountIds = null,
    ): self {
        return new self(
            id: $this->id,
            clientId: $this->clientId,
            organizationId: $this->organizationId,
            name: $name ?? $this->name,
            type: $this->type,
            valueCents: $valueCents ?? $this->valueCents,
            currency: $this->currency,
            startsAt: $this->startsAt,
            endsAt: $endsAt ?? $this->endsAt,
            socialAccountIds: $socialAccountIds ?? $this->socialAccountIds,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function pause(): self
    {
        $this->ensureTransition(ContractStatus::Paused);

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            organizationId: $this->organizationId,
            name: $this->name,
            type: $this->type,
            valueCents: $this->valueCents,
            currency: $this->currency,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            socialAccountIds: $this->socialAccountIds,
            status: ContractStatus::Paused,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function resume(): self
    {
        $this->ensureTransition(ContractStatus::Active);

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            organizationId: $this->organizationId,
            name: $this->name,
            type: $this->type,
            valueCents: $this->valueCents,
            currency: $this->currency,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            socialAccountIds: $this->socialAccountIds,
            status: ContractStatus::Active,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function complete(string $userId): self
    {
        $this->ensureTransition(ContractStatus::Completed);

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            organizationId: $this->organizationId,
            name: $this->name,
            type: $this->type,
            valueCents: $this->valueCents,
            currency: $this->currency,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            socialAccountIds: $this->socialAccountIds,
            status: ContractStatus::Completed,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new ContractCompleted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    clientId: (string) $this->clientId,
                ),
            ],
        );
    }

    public function cancel(): self
    {
        $this->ensureTransition(ContractStatus::Cancelled);

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            organizationId: $this->organizationId,
            name: $this->name,
            type: $this->type,
            valueCents: $this->valueCents,
            currency: $this->currency,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            socialAccountIds: $this->socialAccountIds,
            status: ContractStatus::Cancelled,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array
    {
        return $this->domainEvents;
    }

    private function ensureTransition(ContractStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidContractTransitionException(
                "Não é possível transicionar de '{$this->status->value}' para '{$target->value}'.",
            );
        }
    }
}
