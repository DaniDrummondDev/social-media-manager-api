<?php

declare(strict_types=1);

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(ContentRepositoryInterface::class);
    $this->campaignRepository = app(CampaignRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'content-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'content-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->campaign = Campaign::create(
        organizationId: Uuid::fromString($this->orgId),
        createdBy: Uuid::fromString($this->userId),
        name: 'Test Campaign '.Str::random(4),
    );
    $this->campaignRepository->create($this->campaign);
});

it('creates and retrieves by id', function () {
    $content = Content::create(
        organizationId: Uuid::fromString($this->orgId),
        campaignId: $this->campaign->id,
        createdBy: Uuid::fromString($this->userId),
        title: 'Test Content',
        body: 'Content body',
        hashtags: ['test'],
    );

    $this->repository->create($content);
    $found = $this->repository->findById($content->id);

    expect($found)->not->toBeNull()
        ->and($found->title)->toBe('Test Content')
        ->and($found->status)->toBe(ContentStatus::Draft)
        ->and($found->hashtags)->toBe(['test']);
});

it('returns null for non-existent id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('updates content', function () {
    $content = Content::create(
        organizationId: Uuid::fromString($this->orgId),
        campaignId: $this->campaign->id,
        createdBy: Uuid::fromString($this->userId),
        title: 'Original',
    );

    $this->repository->create($content);

    $updated = $content->update(title: 'Updated Title');
    $this->repository->update($updated);

    $found = $this->repository->findById($content->id);
    expect($found->title)->toBe('Updated Title');
});

it('finds by campaign id excluding deleted', function () {
    $orgId = Uuid::fromString($this->orgId);
    $userId = Uuid::fromString($this->userId);

    $c1 = Content::create(organizationId: $orgId, campaignId: $this->campaign->id, createdBy: $userId, title: 'Content 1');
    $c2 = Content::create(organizationId: $orgId, campaignId: $this->campaign->id, createdBy: $userId, title: 'Content 2');
    $c3 = Content::create(organizationId: $orgId, campaignId: $this->campaign->id, createdBy: $userId, title: 'Deleted');
    $c3 = $c3->softDelete();

    $this->repository->create($c1);
    $this->repository->create($c2);
    $this->repository->create($c3);

    $results = $this->repository->findByCampaignId($this->campaign->id);
    expect($results)->toHaveCount(2);
});

it('counts by campaign and status', function () {
    $orgId = Uuid::fromString($this->orgId);
    $userId = Uuid::fromString($this->userId);

    $draft = Content::create(organizationId: $orgId, campaignId: $this->campaign->id, createdBy: $userId, title: 'Draft');
    $ready = Content::create(organizationId: $orgId, campaignId: $this->campaign->id, createdBy: $userId, title: 'Ready');
    $ready = $ready->transitionTo(ContentStatus::Ready);

    $this->repository->create($draft);
    $this->repository->create($ready);

    $counts = $this->repository->countByCampaignAndStatus($this->campaign->id);

    expect($counts)->toHaveKey('draft')
        ->and($counts['draft'])->toBe(1)
        ->and($counts['ready'])->toBe(1);
});

it('soft deletes content', function () {
    $content = Content::create(
        organizationId: Uuid::fromString($this->orgId),
        campaignId: $this->campaign->id,
        createdBy: Uuid::fromString($this->userId),
        title: 'To Delete',
    );

    $this->repository->create($content);

    $deleted = $content->softDelete();
    $this->repository->update($deleted);

    $found = $this->repository->findById($content->id);
    expect($found->deletedAt)->not->toBeNull();
});

it('deletes permanently', function () {
    $content = Content::create(
        organizationId: Uuid::fromString($this->orgId),
        campaignId: $this->campaign->id,
        createdBy: Uuid::fromString($this->userId),
        title: 'To Purge',
    );

    $this->repository->create($content);
    $this->repository->delete($content->id);

    expect($this->repository->findById($content->id))->toBeNull();
});
