<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\QueryStatus;
use App\Domain\SocialListening\ValueObjects\QueryType;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(ListeningQueryRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'lq-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'lq-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates and retrieves by id', function () {
    $query = ListeningQuery::create(
        organizationId: Uuid::fromString($this->orgId),
        name: 'Brand Monitor',
        type: QueryType::Keyword,
        value: 'social media manager',
        platforms: ['instagram', 'tiktok'],
        userId: $this->userId,
    );

    $this->repository->create($query);

    $found = $this->repository->findById($query->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $query->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->name)->toBe('Brand Monitor')
        ->and($found->type)->toBe(QueryType::Keyword)
        ->and($found->value)->toBe('social media manager')
        ->and($found->platforms)->toBe(['instagram', 'tiktok'])
        ->and($found->status)->toBe(QueryStatus::Active);
});

it('returns null for non-existent id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('finds by organization id with cursor pagination', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 0; $i < 5; $i++) {
        $query = ListeningQuery::create(
            organizationId: $orgId,
            name: "Query {$i}",
            type: QueryType::Keyword,
            value: "value-{$i}",
            platforms: ['instagram'],
            userId: $this->userId,
        );
        $this->repository->create($query);
    }

    $firstPage = $this->repository->findByOrganizationId($orgId, [], null, 3);

    expect($firstPage['items'])->toHaveCount(3)
        ->and($firstPage['next_cursor'])->not->toBeNull();

    $secondPage = $this->repository->findByOrganizationId($orgId, [], $firstPage['next_cursor'], 3);

    expect($secondPage['items'])->toHaveCount(2)
        ->and($secondPage['next_cursor'])->toBeNull();
});

it('finds by organization id with status filter', function () {
    $orgId = Uuid::fromString($this->orgId);

    $active = ListeningQuery::create(
        organizationId: $orgId,
        name: 'Active Query',
        type: QueryType::Keyword,
        value: 'active',
        platforms: ['instagram'],
        userId: $this->userId,
    );
    $this->repository->create($active);

    $paused = $active->pause($this->userId);
    $pausedQuery = ListeningQuery::create(
        organizationId: $orgId,
        name: 'Paused Query',
        type: QueryType::Hashtag,
        value: '#paused',
        platforms: ['tiktok'],
        userId: $this->userId,
    );
    $this->repository->create($pausedQuery);
    $pausedUpdated = $pausedQuery->pause($this->userId);
    $this->repository->update($pausedUpdated);

    $results = $this->repository->findByOrganizationId($orgId, ['status' => 'active']);

    expect($results['items'])->toHaveCount(1)
        ->and($results['items'][0]->name)->toBe('Active Query');
});

it('finds active queries by platform', function () {
    $orgId = Uuid::fromString($this->orgId);

    $insta = ListeningQuery::create(
        organizationId: $orgId,
        name: 'Instagram Query',
        type: QueryType::Keyword,
        value: 'test',
        platforms: ['instagram'],
        userId: $this->userId,
    );
    $this->repository->create($insta);

    $tiktok = ListeningQuery::create(
        organizationId: $orgId,
        name: 'TikTok Query',
        type: QueryType::Hashtag,
        value: '#test',
        platforms: ['tiktok'],
        userId: $this->userId,
    );
    $this->repository->create($tiktok);

    $both = ListeningQuery::create(
        organizationId: $orgId,
        name: 'Both Platforms',
        type: QueryType::Mention,
        value: '@brand',
        platforms: ['instagram', 'tiktok'],
        userId: $this->userId,
    );
    $this->repository->create($both);

    $instagramQueries = $this->repository->findActiveByPlatform('instagram');

    expect($instagramQueries)->toHaveCount(2);
});

it('finds active queries grouped by organization', function () {
    $orgId1 = Uuid::fromString($this->orgId);

    $orgId2Str = (string) Uuid::generate();
    DB::table('organizations')->insert([
        'id' => $orgId2Str,
        'name' => 'Org 2',
        'slug' => 'lq-org2-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
    $orgId2 = Uuid::fromString($orgId2Str);

    $q1 = ListeningQuery::create(
        organizationId: $orgId1,
        name: 'Query Org1',
        type: QueryType::Keyword,
        value: 'keyword1',
        platforms: ['instagram'],
        userId: $this->userId,
    );
    $this->repository->create($q1);

    $q2 = ListeningQuery::create(
        organizationId: $orgId2,
        name: 'Query Org2',
        type: QueryType::Keyword,
        value: 'keyword2',
        platforms: ['tiktok'],
        userId: $this->userId,
    );
    $this->repository->create($q2);

    $grouped = $this->repository->findActiveGroupedByOrganization();

    expect($grouped)->toHaveCount(2)
        ->and($grouped[$this->orgId])->toHaveCount(1)
        ->and($grouped[$orgId2Str])->toHaveCount(1);
});

it('counts queries by organization id', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 0; $i < 3; $i++) {
        $query = ListeningQuery::create(
            organizationId: $orgId,
            name: "Count Query {$i}",
            type: QueryType::Keyword,
            value: "count-{$i}",
            platforms: ['instagram'],
            userId: $this->userId,
        );
        $this->repository->create($query);
    }

    expect($this->repository->countByOrganizationId($orgId))->toBe(3);
});

it('updates a listening query', function () {
    $query = ListeningQuery::create(
        organizationId: Uuid::fromString($this->orgId),
        name: 'Original Name',
        type: QueryType::Keyword,
        value: 'original',
        platforms: ['instagram'],
        userId: $this->userId,
    );

    $this->repository->create($query);

    $updated = $query->updateDetails('Updated Name', 'updated-value', ['instagram', 'tiktok']);
    $this->repository->update($updated);

    $found = $this->repository->findById($query->id);

    expect($found->name)->toBe('Updated Name')
        ->and($found->value)->toBe('updated-value')
        ->and($found->platforms)->toBe(['instagram', 'tiktok']);
});

it('deletes a listening query', function () {
    $query = ListeningQuery::create(
        organizationId: Uuid::fromString($this->orgId),
        name: 'To Delete',
        type: QueryType::Keyword,
        value: 'delete-me',
        platforms: ['instagram'],
        userId: $this->userId,
    );

    $this->repository->create($query);

    $this->repository->delete($query->id);

    expect($this->repository->findById($query->id))->toBeNull();
});
