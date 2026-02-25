<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Entities\ClientContract;
use App\Domain\ClientFinance\Events\ContractCompleted;
use App\Domain\ClientFinance\Events\ContractCreated;
use App\Domain\ClientFinance\Exceptions\InvalidContractTransitionException;
use App\Domain\ClientFinance\ValueObjects\ContractStatus;
use App\Domain\ClientFinance\ValueObjects\ContractType;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Uuid;

function createActiveContract(): ClientContract
{
    return ClientContract::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Contrato Mensal',
        type: ContractType::FixedMonthly,
        valueCents: 500000,
        currency: Currency::BRL,
        startsAt: new DateTimeImmutable('2026-01-01'),
        endsAt: new DateTimeImmutable('2026-12-31'),
        socialAccountIds: [],
        status: ContractStatus::Active,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

function createPausedContract(): ClientContract
{
    return ClientContract::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Contrato Pausado',
        type: ContractType::FixedMonthly,
        valueCents: 500000,
        currency: Currency::BRL,
        startsAt: new DateTimeImmutable('2026-01-01'),
        endsAt: null,
        socialAccountIds: [],
        status: ContractStatus::Paused,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('emits ContractCreated event on create', function () {
    $contract = ClientContract::create(
        clientId: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Contrato Mensal',
        type: ContractType::FixedMonthly,
        valueCents: 500000,
        currency: Currency::BRL,
        startsAt: new DateTimeImmutable('2026-01-01'),
        endsAt: new DateTimeImmutable('2026-12-31'),
        socialAccountIds: [(string) Uuid::generate()],
        userId: (string) Uuid::generate(),
    );

    $events = $contract->releaseEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ContractCreated::class)
        ->and($contract->status)->toBe(ContractStatus::Active)
        ->and($contract->valueCents)->toBe(500000);
});

it('pauses an active contract', function () {
    $contract = createActiveContract();
    $paused = $contract->pause();

    expect($paused->status)->toBe(ContractStatus::Paused);
});

it('resumes a paused contract', function () {
    $contract = createPausedContract();
    $resumed = $contract->resume();

    expect($resumed->status)->toBe(ContractStatus::Active);
});

it('completes an active contract and emits ContractCompleted event', function () {
    $contract = createActiveContract();
    $completed = $contract->complete(userId: (string) Uuid::generate());

    $events = $completed->releaseEvents();

    expect($completed->status)->toBe(ContractStatus::Completed)
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ContractCompleted::class);
});

it('cancels an active contract', function () {
    $contract = createActiveContract();
    $cancelled = $contract->cancel();

    expect($cancelled->status)->toBe(ContractStatus::Cancelled);
});

it('cancels a paused contract', function () {
    $contract = createPausedContract();
    $cancelled = $contract->cancel();

    expect($cancelled->status)->toBe(ContractStatus::Cancelled);
});

it('throws InvalidContractTransitionException for completed -> pause', function () {
    $contract = ClientContract::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Contrato Concluído',
        type: ContractType::FixedMonthly,
        valueCents: 500000,
        currency: Currency::BRL,
        startsAt: new DateTimeImmutable('2026-01-01'),
        endsAt: new DateTimeImmutable('2026-06-30'),
        socialAccountIds: [],
        status: ContractStatus::Completed,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect(fn () => $contract->pause())
        ->toThrow(InvalidContractTransitionException::class);
});

it('throws InvalidContractTransitionException for cancelled -> resume', function () {
    $contract = ClientContract::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Contrato Cancelado',
        type: ContractType::PerCampaign,
        valueCents: 300000,
        currency: Currency::BRL,
        startsAt: new DateTimeImmutable('2026-01-01'),
        endsAt: null,
        socialAccountIds: [],
        status: ContractStatus::Cancelled,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect(fn () => $contract->resume())
        ->toThrow(InvalidContractTransitionException::class);
});

it('updates contract fields', function () {
    $contract = createActiveContract();

    $updated = $contract->update(
        name: 'Contrato Atualizado',
        valueCents: 750000,
    );

    expect($updated->name)->toBe('Contrato Atualizado')
        ->and($updated->valueCents)->toBe(750000)
        ->and($updated->status)->toBe(ContractStatus::Active);
});

it('has no events when reconstituted', function () {
    $contract = createActiveContract();

    expect($contract->releaseEvents())->toBeEmpty();
});
