<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\QueryType;
use App\Domain\SocialListening\ValueObjects\Sentiment;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(MentionRepositoryInterface::class);
    $this->queryRepository = app(ListeningQueryRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'mention-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'mention-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->listeningQuery = ListeningQuery::create(
        organizationId: Uuid::fromString($this->orgId),
        name: 'Test Query',
        type: QueryType::Keyword,
        value: 'test keyword',
        platforms: ['instagram'],
        userId: $this->userId,
    );
    $this->queryRepository->create($this->listeningQuery);

    // Period range that covers "now" - used by all time-based queries
    $this->periodFrom = new \DateTimeImmutable('2020-01-01 00:00:00');
    $this->periodTo = new \DateTimeImmutable('2030-12-31 23:59:59');
});

it('creates and retrieves by id', function () {
    $mention = Mention::create(
        queryId: $this->listeningQuery->id,
        organizationId: Uuid::fromString($this->orgId),
        platform: 'instagram',
        externalId: 'ext-123',
        authorUsername: 'johndoe',
        authorDisplayName: 'John Doe',
        authorFollowerCount: 1500,
        profileUrl: 'https://instagram.com/johndoe',
        content: 'Great product!',
        url: 'https://instagram.com/p/abc123',
        sentiment: Sentiment::Positive,
        sentimentScore: 0.8500,
        reach: 5000,
        engagementCount: 150,
        publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
    );

    $this->repository->create($mention);

    $found = $this->repository->findById($mention->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $mention->id)
        ->and((string) $found->queryId)->toBe((string) $this->listeningQuery->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->platform)->toBe('instagram')
        ->and($found->externalId)->toBe('ext-123')
        ->and($found->authorUsername)->toBe('johndoe')
        ->and($found->authorDisplayName)->toBe('John Doe')
        ->and($found->authorFollowerCount)->toBe(1500)
        ->and($found->content)->toBe('Great product!')
        ->and($found->sentiment)->toBe(Sentiment::Positive)
        ->and($found->reach)->toBe(5000)
        ->and($found->engagementCount)->toBe(150)
        ->and($found->isFlagged)->toBeFalse()
        ->and($found->isRead)->toBeFalse();
});

it('returns null for non-existent id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('finds by organization id with cursor pagination', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 0; $i < 5; $i++) {
        $mention = Mention::create(
            queryId: $this->listeningQuery->id,
            organizationId: $orgId,
            platform: 'instagram',
            externalId: "ext-page-{$i}",
            authorUsername: "user{$i}",
            authorDisplayName: "User {$i}",
            authorFollowerCount: 100 * $i,
            profileUrl: null,
            content: "Mention content {$i}",
            url: null,
            sentiment: Sentiment::Neutral,
            sentimentScore: 0.5000,
            reach: 100,
            engagementCount: 10,
            publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
        );
        $this->repository->create($mention);
    }

    $firstPage = $this->repository->findByOrganizationId($orgId, [], null, 3);

    expect($firstPage['items'])->toHaveCount(3)
        ->and($firstPage['next_cursor'])->not->toBeNull();

    $secondPage = $this->repository->findByOrganizationId($orgId, [], $firstPage['next_cursor'], 3);

    expect($secondPage['items'])->toHaveCount(2)
        ->and($secondPage['next_cursor'])->toBeNull();
});

it('counts by organization in period', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 0; $i < 3; $i++) {
        $mention = Mention::create(
            queryId: $this->listeningQuery->id,
            organizationId: $orgId,
            platform: 'instagram',
            externalId: "ext-count-{$i}",
            authorUsername: "user{$i}",
            authorDisplayName: "User {$i}",
            authorFollowerCount: null,
            profileUrl: null,
            content: "Content {$i}",
            url: null,
            sentiment: Sentiment::Positive,
            sentimentScore: 0.9000,
            reach: 50,
            engagementCount: 5,
            publishedAt: new \DateTimeImmutable('2026-02-20 12:00:00'),
        );
        $this->repository->create($mention);
    }

    $count = $this->repository->countByOrganizationInPeriod($orgId, $this->periodFrom, $this->periodTo);

    expect($count)->toBe(3);
});

it('gets sentiment counts', function () {
    $orgId = Uuid::fromString($this->orgId);

    $sentiments = [Sentiment::Positive, Sentiment::Positive, Sentiment::Negative, Sentiment::Neutral];

    foreach ($sentiments as $i => $sentiment) {
        $mention = Mention::create(
            queryId: $this->listeningQuery->id,
            organizationId: $orgId,
            platform: 'instagram',
            externalId: "ext-sent-{$i}",
            authorUsername: "user{$i}",
            authorDisplayName: "User {$i}",
            authorFollowerCount: null,
            profileUrl: null,
            content: "Sentiment content {$i}",
            url: null,
            sentiment: $sentiment,
            sentimentScore: 0.5000,
            reach: 100,
            engagementCount: 10,
            publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
        );
        $this->repository->create($mention);
    }

    $counts = $this->repository->getSentimentCounts($orgId, $this->periodFrom, $this->periodTo);

    expect($counts['positive'])->toBe(2)
        ->and($counts['negative'])->toBe(1)
        ->and($counts['neutral'])->toBe(1);
});

it('gets sentiment trend', function () {
    $orgId = Uuid::fromString($this->orgId);

    $mention1 = Mention::create(
        queryId: $this->listeningQuery->id,
        organizationId: $orgId,
        platform: 'instagram',
        externalId: 'ext-trend-1',
        authorUsername: 'user1',
        authorDisplayName: 'User 1',
        authorFollowerCount: null,
        profileUrl: null,
        content: 'Positive trend',
        url: null,
        sentiment: Sentiment::Positive,
        sentimentScore: 0.9000,
        reach: 100,
        engagementCount: 10,
        publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
    );
    $this->repository->create($mention1);

    $mention2 = Mention::create(
        queryId: $this->listeningQuery->id,
        organizationId: $orgId,
        platform: 'instagram',
        externalId: 'ext-trend-2',
        authorUsername: 'user2',
        authorDisplayName: 'User 2',
        authorFollowerCount: null,
        profileUrl: null,
        content: 'Negative trend',
        url: null,
        sentiment: Sentiment::Negative,
        sentimentScore: 0.2000,
        reach: 100,
        engagementCount: 10,
        publishedAt: new \DateTimeImmutable('2026-02-20 14:00:00'),
    );
    $this->repository->create($mention2);

    $trend = $this->repository->getSentimentTrend($orgId, $this->periodFrom, $this->periodTo);

    // Both mentions have the same detected_at date (today), so should group into 1 day
    expect($trend)->toHaveCount(1)
        ->and($trend[0]['positive'])->toBe(1)
        ->and($trend[0]['negative'])->toBe(1)
        ->and($trend[0]['total'])->toBe(2);
});

it('gets top authors', function () {
    $orgId = Uuid::fromString($this->orgId);

    // Create 3 mentions for user_a and 1 for user_b
    for ($i = 0; $i < 3; $i++) {
        $mention = Mention::create(
            queryId: $this->listeningQuery->id,
            organizationId: $orgId,
            platform: 'instagram',
            externalId: "ext-author-a-{$i}",
            authorUsername: 'user_a',
            authorDisplayName: 'User A',
            authorFollowerCount: 5000,
            profileUrl: null,
            content: "Content from user_a {$i}",
            url: null,
            sentiment: Sentiment::Positive,
            sentimentScore: 0.8000,
            reach: 100,
            engagementCount: 10,
            publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
        );
        $this->repository->create($mention);
    }

    $mentionB = Mention::create(
        queryId: $this->listeningQuery->id,
        organizationId: $orgId,
        platform: 'instagram',
        externalId: 'ext-author-b-0',
        authorUsername: 'user_b',
        authorDisplayName: 'User B',
        authorFollowerCount: 2000,
        profileUrl: null,
        content: 'Content from user_b',
        url: null,
        sentiment: Sentiment::Neutral,
        sentimentScore: 0.5000,
        reach: 50,
        engagementCount: 5,
        publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
    );
    $this->repository->create($mentionB);

    $topAuthors = $this->repository->getTopAuthors($orgId, $this->periodFrom, $this->periodTo, null, 10);

    expect($topAuthors)->toHaveCount(2)
        ->and($topAuthors[0]['author_username'])->toBe('user_a')
        ->and($topAuthors[0]['count'])->toBe(3)
        ->and($topAuthors[1]['author_username'])->toBe('user_b')
        ->and($topAuthors[1]['count'])->toBe(1);
});

it('gets platform breakdown', function () {
    $orgId = Uuid::fromString($this->orgId);

    $platforms = ['instagram', 'instagram', 'tiktok'];

    foreach ($platforms as $i => $platform) {
        $mention = Mention::create(
            queryId: $this->listeningQuery->id,
            organizationId: $orgId,
            platform: $platform,
            externalId: "ext-plat-{$i}",
            authorUsername: "user{$i}",
            authorDisplayName: "User {$i}",
            authorFollowerCount: null,
            profileUrl: null,
            content: "Platform content {$i}",
            url: null,
            sentiment: Sentiment::Neutral,
            sentimentScore: 0.5000,
            reach: 100,
            engagementCount: 10,
            publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
        );
        $this->repository->create($mention);
    }

    $breakdown = $this->repository->getPlatformBreakdown($orgId, $this->periodFrom, $this->periodTo);

    expect($breakdown)->toHaveCount(2);

    $instagramEntry = collect($breakdown)->firstWhere('platform', 'instagram');
    $tiktokEntry = collect($breakdown)->firstWhere('platform', 'tiktok');

    expect($instagramEntry['count'])->toBe(2)
        ->and($tiktokEntry['count'])->toBe(1);
});

it('counts by query in period', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 0; $i < 4; $i++) {
        $mention = Mention::create(
            queryId: $this->listeningQuery->id,
            organizationId: $orgId,
            platform: 'instagram',
            externalId: "ext-qcount-{$i}",
            authorUsername: "user{$i}",
            authorDisplayName: "User {$i}",
            authorFollowerCount: null,
            profileUrl: null,
            content: "Query count content {$i}",
            url: null,
            sentiment: Sentiment::Positive,
            sentimentScore: 0.8000,
            reach: 100,
            engagementCount: 10,
            publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
        );
        $this->repository->create($mention);
    }

    $count = $this->repository->countByQueryInPeriod($this->listeningQuery->id, $this->periodFrom, $this->periodTo);

    expect($count)->toBe(4);
});

it('finds by query id', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 0; $i < 3; $i++) {
        $mention = Mention::create(
            queryId: $this->listeningQuery->id,
            organizationId: $orgId,
            platform: 'instagram',
            externalId: "ext-byq-{$i}",
            authorUsername: "user{$i}",
            authorDisplayName: "User {$i}",
            authorFollowerCount: null,
            profileUrl: null,
            content: "By query content {$i}",
            url: null,
            sentiment: Sentiment::Positive,
            sentimentScore: 0.7000,
            reach: 100,
            engagementCount: 10,
            publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
        );
        $this->repository->create($mention);
    }

    $results = $this->repository->findByQueryId($this->listeningQuery->id);

    expect($results)->toHaveCount(3);
});

it('deletes mentions older than a date', function () {
    $orgId = Uuid::fromString($this->orgId);

    // Create an old mention
    $oldMention = Mention::create(
        queryId: $this->listeningQuery->id,
        organizationId: $orgId,
        platform: 'instagram',
        externalId: 'ext-old-1',
        authorUsername: 'old_user',
        authorDisplayName: 'Old User',
        authorFollowerCount: null,
        profileUrl: null,
        content: 'Old mention',
        url: null,
        sentiment: Sentiment::Neutral,
        sentimentScore: 0.5000,
        reach: 50,
        engagementCount: 5,
        publishedAt: new \DateTimeImmutable('2025-01-01 10:00:00'),
    );
    $this->repository->create($oldMention);

    // Update detected_at to the past manually
    DB::table('mentions')
        ->where('id', (string) $oldMention->id)
        ->update(['detected_at' => '2025-01-01 10:00:00']);

    // Create a recent mention
    $recentMention = Mention::create(
        queryId: $this->listeningQuery->id,
        organizationId: $orgId,
        platform: 'instagram',
        externalId: 'ext-recent-1',
        authorUsername: 'recent_user',
        authorDisplayName: 'Recent User',
        authorFollowerCount: null,
        profileUrl: null,
        content: 'Recent mention',
        url: null,
        sentiment: Sentiment::Positive,
        sentimentScore: 0.9000,
        reach: 100,
        engagementCount: 10,
        publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
    );
    $this->repository->create($recentMention);

    $deleted = $this->repository->deleteOlderThan(new \DateTimeImmutable('2026-01-01 00:00:00'));

    expect($deleted)->toBe(1)
        ->and($this->repository->findById($oldMention->id))->toBeNull()
        ->and($this->repository->findById($recentMention->id))->not->toBeNull();
});

it('updates a mention', function () {
    $orgId = Uuid::fromString($this->orgId);

    $mention = Mention::create(
        queryId: $this->listeningQuery->id,
        organizationId: $orgId,
        platform: 'instagram',
        externalId: 'ext-update-1',
        authorUsername: 'updater',
        authorDisplayName: 'Updater',
        authorFollowerCount: null,
        profileUrl: null,
        content: 'Original content',
        url: null,
        sentiment: null,
        sentimentScore: null,
        reach: 100,
        engagementCount: 10,
        publishedAt: new \DateTimeImmutable('2026-02-20 10:00:00'),
    );
    $this->repository->create($mention);

    $flagged = $mention->flag($this->userId);
    $this->repository->update($flagged);

    $found = $this->repository->findById($mention->id);

    expect($found->isFlagged)->toBeTrue();
});
