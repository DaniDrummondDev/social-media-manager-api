<?php

declare(strict_types=1);

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(CampaignRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'camp-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'camp-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates and retrieves by id', function () {
    $campaign = Campaign::create(
        organizationId: Uuid::fromString($this->orgId),
        createdBy: Uuid::fromString($this->userId),
        name: 'Test Campaign',
        description: 'A test',
        tags: ['tag1', 'tag2'],
    );

    $this->repository->create($campaign);

    $found = $this->repository->findById($campaign->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $campaign->id)
        ->and($found->name)->toBe('Test Campaign')
        ->and($found->description)->toBe('A test')
        ->and($found->status)->toBe(CampaignStatus::Draft)
        ->and($found->tags)->toBe(['tag1', 'tag2']);
});

it('returns null for non-existent id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('updates campaign fields', function () {
    $campaign = Campaign::create(
        organizationId: Uuid::fromString($this->orgId),
        createdBy: Uuid::fromString($this->userId),
        name: 'Original',
    );

    $this->repository->create($campaign);

    $updated = $campaign->update(name: 'Updated', status: CampaignStatus::Active);
    $this->repository->update($updated);

    $found = $this->repository->findById($campaign->id);
    expect($found->name)->toBe('Updated')
        ->and($found->status)->toBe(CampaignStatus::Active);
});

it('finds by organization id excluding deleted', function () {
    $orgId = Uuid::fromString($this->orgId);
    $userId = Uuid::fromString($this->userId);

    $c1 = Campaign::create(organizationId: $orgId, createdBy: $userId, name: 'Campaign 1');
    $c2 = Campaign::create(organizationId: $orgId, createdBy: $userId, name: 'Campaign 2');
    $c3 = Campaign::create(organizationId: $orgId, createdBy: $userId, name: 'Deleted');
    $c3 = $c3->softDelete();

    $this->repository->create($c1);
    $this->repository->create($c2);
    $this->repository->create($c3);

    $results = $this->repository->findByOrganizationId($orgId);

    expect($results)->toHaveCount(2);
});

it('checks name uniqueness case-insensitively', function () {
    $orgId = Uuid::fromString($this->orgId);
    $userId = Uuid::fromString($this->userId);

    $campaign = Campaign::create(organizationId: $orgId, createdBy: $userId, name: 'My Campaign');
    $this->repository->create($campaign);

    expect($this->repository->existsByOrganizationAndName($orgId, 'my campaign'))->toBeTrue()
        ->and($this->repository->existsByOrganizationAndName($orgId, 'MY CAMPAIGN'))->toBeTrue()
        ->and($this->repository->existsByOrganizationAndName($orgId, 'Other Name'))->toBeFalse();
});

it('excludes id in name uniqueness check', function () {
    $orgId = Uuid::fromString($this->orgId);
    $userId = Uuid::fromString($this->userId);

    $campaign = Campaign::create(organizationId: $orgId, createdBy: $userId, name: 'My Campaign');
    $this->repository->create($campaign);

    expect($this->repository->existsByOrganizationAndName($orgId, 'My Campaign', $campaign->id))->toBeFalse();
});

it('soft deletes and retrieves with deleted_at', function () {
    $campaign = Campaign::create(
        organizationId: Uuid::fromString($this->orgId),
        createdBy: Uuid::fromString($this->userId),
        name: 'To Delete',
    );

    $this->repository->create($campaign);

    $deleted = $campaign->softDelete();
    $this->repository->update($deleted);

    $found = $this->repository->findById($campaign->id);
    expect($found->deletedAt)->not->toBeNull()
        ->and($found->purgeAt)->not->toBeNull();
});

it('deletes permanently', function () {
    $campaign = Campaign::create(
        organizationId: Uuid::fromString($this->orgId),
        createdBy: Uuid::fromString($this->userId),
        name: 'To Purge',
    );

    $this->repository->create($campaign);
    $this->repository->delete($campaign->id);

    expect($this->repository->findById($campaign->id))->toBeNull();
});
