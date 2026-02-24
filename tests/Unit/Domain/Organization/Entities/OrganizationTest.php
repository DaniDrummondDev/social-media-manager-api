<?php

declare(strict_types=1);

use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Events\OrganizationCreated;
use App\Domain\Organization\Events\OrganizationDeleted;
use App\Domain\Organization\Events\OrganizationUpdated;
use App\Domain\Organization\Exceptions\InvalidOrganizationNameException;
use App\Domain\Organization\Exceptions\OrganizationAlreadyDeletedException;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Organization\ValueObjects\OrganizationStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestOrganization(): Organization
{
    return Organization::create(
        name: 'Acme Corp',
        slug: OrganizationSlug::fromString('acme-corp'),
        ownerId: Uuid::generate(),
    );
}

it('creates an organization with default values', function () {
    $org = createTestOrganization();

    expect($org->name)->toBe('Acme Corp')
        ->and((string) $org->slug)->toBe('acme-corp')
        ->and($org->timezone)->toBe('America/Sao_Paulo')
        ->and($org->status)->toBe(OrganizationStatus::Active)
        ->and($org->isActive())->toBeTrue();
});

it('records OrganizationCreated event on creation', function () {
    $org = createTestOrganization();

    expect($org->domainEvents)->toHaveCount(1)
        ->and($org->domainEvents[0])->toBeInstanceOf(OrganizationCreated::class)
        ->and($org->domainEvents[0]->name)->toBe('Acme Corp');
});

it('rejects empty organization name', function () {
    Organization::create(
        name: '',
        slug: OrganizationSlug::fromString('acme-corp'),
        ownerId: Uuid::generate(),
    );
})->throws(InvalidOrganizationNameException::class);

it('rejects organization name longer than 200 chars', function () {
    Organization::create(
        name: str_repeat('a', 201),
        slug: OrganizationSlug::fromString('acme-corp'),
        ownerId: Uuid::generate(),
    );
})->throws(InvalidOrganizationNameException::class);

it('reconstitutes an organization without events', function () {
    $org = createTestOrganization();

    $reconstituted = Organization::reconstitute(
        id: $org->id,
        name: $org->name,
        slug: $org->slug,
        timezone: $org->timezone,
        status: $org->status,
        createdAt: $org->createdAt,
        updatedAt: $org->updatedAt,
    );

    expect($reconstituted->domainEvents)->toBeEmpty()
        ->and($reconstituted->name)->toBe('Acme Corp');
});

it('updates organization name', function () {
    $org = createTestOrganization();
    $updated = $org->update(name: 'New Corp');

    expect($updated->name)->toBe('New Corp')
        ->and($updated->domainEvents)->toHaveCount(2)
        ->and($updated->domainEvents[1])->toBeInstanceOf(OrganizationUpdated::class)
        ->and($updated->domainEvents[1]->changes)->toHaveKey('name');
});

it('updates organization slug', function () {
    $org = createTestOrganization();
    $newSlug = OrganizationSlug::fromString('new-corp');
    $updated = $org->update(slug: $newSlug);

    expect((string) $updated->slug)->toBe('new-corp');
});

it('returns same instance when no changes', function () {
    $org = createTestOrganization();
    $updated = $org->update(name: 'Acme Corp');

    expect($updated)->toBe($org);
});

it('marks organization as deleted', function () {
    $org = createTestOrganization();
    $deleted = $org->markAsDeleted();

    expect($deleted->status)->toBe(OrganizationStatus::Deleted)
        ->and($deleted->isActive())->toBeFalse()
        ->and($deleted->domainEvents)->toHaveCount(2)
        ->and($deleted->domainEvents[1])->toBeInstanceOf(OrganizationDeleted::class);
});

it('throws when deleting already deleted organization', function () {
    $org = createTestOrganization()->markAsDeleted();
    $org->markAsDeleted();
})->throws(OrganizationAlreadyDeletedException::class);

it('suspends an organization', function () {
    $org = createTestOrganization();
    $suspended = $org->suspend();

    expect($suspended->status)->toBe(OrganizationStatus::Suspended)
        ->and($suspended->isActive())->toBeFalse()
        ->and($suspended->domainEvents)->toHaveCount(2)
        ->and($suspended->domainEvents[1])->toBeInstanceOf(OrganizationUpdated::class);
});

it('reactivates a suspended organization', function () {
    $org = createTestOrganization()->suspend();
    $reactivated = $org->reactivate();

    expect($reactivated->status)->toBe(OrganizationStatus::Active)
        ->and($reactivated->isActive())->toBeTrue();
});

it('throws when reactivating a deleted organization', function () {
    $org = createTestOrganization()->markAsDeleted();
    $org->reactivate();
})->throws(OrganizationAlreadyDeletedException::class);

it('throws when updating a deleted organization', function () {
    $org = createTestOrganization()->markAsDeleted();
    $org->update(name: 'New Name');
})->throws(OrganizationAlreadyDeletedException::class);

it('releases domain events', function () {
    $org = createTestOrganization();
    expect($org->domainEvents)->toHaveCount(1);

    $released = $org->releaseEvents();
    expect($released->domainEvents)->toBeEmpty();
});
