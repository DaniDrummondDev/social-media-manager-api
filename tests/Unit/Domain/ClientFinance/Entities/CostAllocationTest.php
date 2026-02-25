<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Entities\CostAllocation;
use App\Domain\ClientFinance\Events\CostAllocated;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\ClientFinance\ValueObjects\ResourceType;
use App\Domain\Shared\ValueObjects\Uuid;

it('emits CostAllocated event on create', function () {
    $clientId = Uuid::generate();
    $organizationId = Uuid::generate();
    $resourceId = Uuid::generate();
    $userId = (string) Uuid::generate();

    $allocation = CostAllocation::create(
        clientId: $clientId,
        organizationId: $organizationId,
        resourceType: ResourceType::Campaign,
        resourceId: $resourceId,
        description: 'Custo de campanha Q1',
        costCents: 75000,
        currency: Currency::BRL,
        userId: $userId,
    );

    $events = $allocation->releaseEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(CostAllocated::class)
        ->and($events[0]->clientId)->toBe((string) $clientId)
        ->and($events[0]->costCents)->toBe(75000)
        ->and($events[0]->resourceType)->toBe('campaign')
        ->and($allocation->costCents)->toBe(75000)
        ->and($allocation->resourceType)->toBe(ResourceType::Campaign)
        ->and($allocation->description)->toBe('Custo de campanha Q1');
});

it('creates allocation with null resourceId', function () {
    $allocation = CostAllocation::create(
        clientId: Uuid::generate(),
        organizationId: Uuid::generate(),
        resourceType: ResourceType::AiGeneration,
        resourceId: null,
        description: 'Custo IA geral',
        costCents: 5000,
        currency: Currency::USD,
        userId: (string) Uuid::generate(),
    );

    expect($allocation->resourceId)->toBeNull()
        ->and($allocation->currency)->toBe(Currency::USD);
});

it('has no events when reconstituted', function () {
    $allocation = CostAllocation::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        organizationId: Uuid::generate(),
        resourceType: ResourceType::MediaStorage,
        resourceId: Uuid::generate(),
        description: 'Armazenamento de mídia',
        costCents: 12000,
        currency: Currency::BRL,
        allocatedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
    );

    expect($allocation->releaseEvents())->toBeEmpty();
});
