<?php

declare(strict_types=1);

use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Organization\ValueObjects\OrganizationStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->repository = app(OrganizationRepositoryInterface::class);
    $this->user = $this->createUserInDb();
});

it('creates an organization and retrieves by id', function () {
    $org = Organization::create(
        name: 'Acme Corp',
        slug: OrganizationSlug::fromString('acme-corp'),
        ownerId: Uuid::fromString($this->user['id']),
    );

    $this->repository->create($org);

    $found = $this->repository->findById($org->id);

    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('Acme Corp')
        ->and((string) $found->slug)->toBe('acme-corp')
        ->and($found->status)->toBe(OrganizationStatus::Active);
});

it('returns null when organization not found by id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('finds organization by slug', function () {
    $org = Organization::create(
        name: 'Slug Test',
        slug: OrganizationSlug::fromString('slug-test'),
        ownerId: Uuid::fromString($this->user['id']),
    );

    $this->repository->create($org);

    $found = $this->repository->findBySlug(OrganizationSlug::fromString('slug-test'));

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $org->id);
});

it('updates organization fields', function () {
    $org = Organization::create(
        name: 'Original',
        slug: OrganizationSlug::fromString('original'),
        ownerId: Uuid::fromString($this->user['id']),
    );

    $this->repository->create($org);

    $updated = $org->update(name: 'Updated Corp', timezone: 'Europe/London');
    $this->repository->update($updated);

    $found = $this->repository->findById($org->id);

    expect($found->name)->toBe('Updated Corp')
        ->and($found->timezone)->toBe('Europe/London');
});

it('deletes an organization', function () {
    $org = Organization::create(
        name: 'ToDelete',
        slug: OrganizationSlug::fromString('to-delete'),
        ownerId: Uuid::fromString($this->user['id']),
    );

    $this->repository->create($org);
    $this->repository->delete($org->id);

    expect($this->repository->findById($org->id))->toBeNull();
});

it('lists organizations by user id', function () {
    $userId = Uuid::fromString($this->user['id']);

    $org1 = Organization::create(name: 'Org A', slug: OrganizationSlug::fromString('org-a'), ownerId: $userId);
    $org2 = Organization::create(name: 'Org B', slug: OrganizationSlug::fromString('org-b'), ownerId: $userId);

    $this->repository->create($org1);
    $this->repository->create($org2);

    // Insert membership records directly (org already exists via repository)
    foreach ([$org1, $org2] as $org) {
        DB::table('organization_members')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => (string) $org->id,
            'user_id' => $this->user['id'],
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now()->toDateTimeString(),
        ]);
    }

    $list = $this->repository->listByUserId($userId);

    expect($list)->toHaveCount(2);
});
