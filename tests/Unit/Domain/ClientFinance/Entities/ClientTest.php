<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Entities\Client;
use App\Domain\ClientFinance\Events\ClientArchived;
use App\Domain\ClientFinance\Events\ClientCreated;
use App\Domain\ClientFinance\Events\ClientUpdated;
use App\Domain\ClientFinance\Exceptions\ClientAlreadyArchivedException;
use App\Domain\ClientFinance\ValueObjects\ClientStatus;
use App\Domain\Shared\ValueObjects\Uuid;

it('emits ClientCreated event on create', function () {
    $client = Client::create(
        organizationId: Uuid::generate(),
        name: 'Acme Corp',
        userId: (string) Uuid::generate(),
    );

    $events = $client->releaseEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ClientCreated::class)
        ->and($events[0]->clientName)->toBe('Acme Corp')
        ->and($client->name)->toBe('Acme Corp')
        ->and($client->status)->toBe(ClientStatus::Active);
});

it('emits ClientUpdated event on update', function () {
    $client = Client::create(
        organizationId: Uuid::generate(),
        name: 'Acme Corp',
        userId: (string) Uuid::generate(),
    );

    $updated = $client->update(
        userId: (string) Uuid::generate(),
        name: 'Acme Corp Updated',
        email: 'acme@example.com',
    );

    $events = $updated->releaseEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ClientUpdated::class)
        ->and($updated->name)->toBe('Acme Corp Updated')
        ->and($updated->email)->toBe('acme@example.com');
});

it('emits ClientArchived event and sets deletedAt/purgeAt on archive', function () {
    $client = Client::create(
        organizationId: Uuid::generate(),
        name: 'Acme Corp',
        userId: (string) Uuid::generate(),
    );

    $archived = $client->archive(
        userId: (string) Uuid::generate(),
        graceDays: 90,
    );

    $events = $archived->releaseEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ClientArchived::class)
        ->and($archived->status)->toBe(ClientStatus::Archived)
        ->and($archived->deletedAt)->not->toBeNull()
        ->and($archived->purgeAt)->not->toBeNull()
        ->and($archived->purgeAt > $archived->deletedAt)->toBeTrue();
});

it('throws ClientAlreadyArchivedException when archiving already archived client', function () {
    $client = Client::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Archived Client',
        email: null,
        phone: null,
        companyName: null,
        taxId: null,
        billingAddress: null,
        notes: null,
        status: ClientStatus::Archived,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: new DateTimeImmutable,
        purgeAt: new DateTimeImmutable('+90 days'),
    );

    expect(fn () => $client->archive(userId: (string) Uuid::generate()))
        ->toThrow(ClientAlreadyArchivedException::class);
});

it('returns true for isActive when status is active', function () {
    $client = Client::create(
        organizationId: Uuid::generate(),
        name: 'Active Client',
        userId: (string) Uuid::generate(),
    );

    expect($client->isActive())->toBeTrue()
        ->and($client->isArchived())->toBeFalse();
});

it('returns true for isArchived when status is archived', function () {
    $client = Client::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Archived Client',
        email: null,
        phone: null,
        companyName: null,
        taxId: null,
        billingAddress: null,
        notes: null,
        status: ClientStatus::Archived,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: new DateTimeImmutable,
        purgeAt: new DateTimeImmutable('+90 days'),
    );

    expect($client->isArchived())->toBeTrue()
        ->and($client->isActive())->toBeFalse();
});

it('has no events when reconstituted', function () {
    $client = Client::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Existing Client',
        email: 'test@example.com',
        phone: null,
        companyName: null,
        taxId: null,
        billingAddress: null,
        notes: null,
        status: ClientStatus::Active,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
    );

    expect($client->releaseEvents())->toBeEmpty();
});
