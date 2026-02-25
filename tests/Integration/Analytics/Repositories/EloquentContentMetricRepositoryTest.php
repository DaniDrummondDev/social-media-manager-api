<?php

declare(strict_types=1);

use App\Domain\Analytics\Entities\ContentMetric;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Analytics\Repositories\EloquentContentMetricRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    // Create org, user, campaign, content, social account
    $this->userId = (string) Str::uuid();
    $this->orgId = (string) Str::uuid();
    $this->contentId = (string) Str::uuid();
    $this->accountId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test',
        'email' => 'test-'.Str::random(6).'@example.com',
        'password' => 'hashed',
        'timezone' => 'UTC',
        'email_verified_at' => now()->toDateTimeString(),
        'two_factor_enabled' => false,
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'test-'.Str::random(4),
        'timezone' => 'UTC',
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('social_accounts')->insert([
        'id' => $this->accountId,
        'organization_id' => $this->orgId,
        'connected_by' => $this->userId,
        'provider' => 'instagram',
        'provider_user_id' => 'ig-001',
        'username' => '@test',
        'display_name' => 'Test',
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'token_expires_at' => now()->addDays(30)->toDateTimeString(),
        'scopes' => json_encode(['read']),
        'status' => 'connected',
        'connected_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $campaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->userId,
        'name' => 'Test Campaign',
        'status' => 'draft',
        'tags' => json_encode([]),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('contents')->insert([
        'id' => $this->contentId,
        'organization_id' => $this->orgId,
        'campaign_id' => $campaignId,
        'created_by' => $this->userId,
        'title' => 'Test Content',
        'body' => 'Body',
        'hashtags' => json_encode([]),
        'status' => 'ready',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('upserts and finds by content and account', function () {
    $repo = app(EloquentContentMetricRepository::class);

    $metric = ContentMetric::create(
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalPostId: 'ext-123',
        impressions: 100,
        reach: 50,
        likes: 10,
        comments: 2,
        shares: 1,
        saves: 0,
        clicks: 5,
        views: null,
        watchTimeSeconds: null,
        organizationId: $this->orgId,
        userId: $this->userId,
    );

    $repo->upsert($metric);

    $found = $repo->findByContentAndAccount(
        Uuid::fromString($this->contentId),
        Uuid::fromString($this->accountId),
    );

    expect($found)->not->toBeNull()
        ->and($found->impressions)->toBe(100)
        ->and($found->likes)->toBe(10);
});

it('upserts updates existing record', function () {
    $repo = app(EloquentContentMetricRepository::class);

    $metric = ContentMetric::create(
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalPostId: 'ext-123',
        impressions: 100,
        reach: 50,
        likes: 10,
        comments: 2,
        shares: 1,
        saves: 0,
        clicks: 5,
        views: null,
        watchTimeSeconds: null,
        organizationId: $this->orgId,
        userId: $this->userId,
    );

    $repo->upsert($metric);

    $updated = $metric->updateMetrics(
        impressions: 200,
        reach: 100,
        likes: 30,
        comments: 5,
        shares: 3,
        saves: 1,
        clicks: 10,
        views: null,
        watchTimeSeconds: null,
        organizationId: $this->orgId,
        userId: $this->userId,
    );

    $repo->upsert($updated);

    $found = $repo->findByContentAndAccount(
        Uuid::fromString($this->contentId),
        Uuid::fromString($this->accountId),
    );

    expect($found->impressions)->toBe(200)
        ->and($found->likes)->toBe(30);
});

it('returns aggregated metrics', function () {
    $repo = app(EloquentContentMetricRepository::class);

    $metric = ContentMetric::create(
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalPostId: 'ext-123',
        impressions: 1000,
        reach: 500,
        likes: 100,
        comments: 20,
        shares: 10,
        saves: 5,
        clicks: 50,
        views: null,
        watchTimeSeconds: null,
        organizationId: $this->orgId,
        userId: $this->userId,
    );

    $repo->upsert($metric);

    $period = MetricPeriod::fromPreset('30d');
    $aggregated = $repo->getAggregatedMetrics(Uuid::fromString($this->orgId), $period);

    expect((int) $aggregated['impressions'])->toBe(1000)
        ->and((int) $aggregated['likes'])->toBe(100);
});
