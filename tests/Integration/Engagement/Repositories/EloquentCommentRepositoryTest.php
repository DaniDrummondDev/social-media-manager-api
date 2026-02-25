<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Engagement\Repositories\EloquentCommentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
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

it('creates and finds by id', function () {
    $repo = app(EloquentCommentRepository::class);

    $comment = Comment::create(
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-123',
        authorName: 'Author',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'Great content!',
        sentiment: Sentiment::Positive,
        sentimentScore: 0.85,
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    $repo->create($comment->releaseEvents());

    $found = $repo->findById($comment->id);

    expect($found)->not->toBeNull()
        ->and($found->text)->toBe('Great content!')
        ->and($found->sentiment)->toBe(Sentiment::Positive)
        ->and($found->isRead)->toBeFalse();
});

it('finds by organization with filters', function () {
    $repo = app(EloquentCommentRepository::class);

    $comment1 = Comment::create(
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-1',
        authorName: 'Author 1',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'Positive comment',
        sentiment: Sentiment::Positive,
        sentimentScore: 0.9,
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    $comment2 = Comment::create(
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-2',
        authorName: 'Author 2',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'Negative comment',
        sentiment: Sentiment::Negative,
        sentimentScore: 0.2,
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    $repo->create($comment1->releaseEvents());
    $repo->create($comment2->releaseEvents());

    $positive = $repo->findByOrganizationId(
        Uuid::fromString($this->orgId),
        ['sentiment' => 'positive'],
    );

    expect($positive)->toHaveCount(1)
        ->and($positive[0]->text)->toBe('Positive comment');
});

it('marks as read', function () {
    $repo = app(EloquentCommentRepository::class);

    $comment = Comment::create(
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-mark-read',
        authorName: 'Author',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'Unread comment',
        sentiment: null,
        sentimentScore: null,
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    $repo->create($comment->releaseEvents());

    $repo->markAsRead($comment->id);

    $found = $repo->findById($comment->id);
    expect($found->isRead)->toBeTrue();
});

it('marks many as read', function () {
    $repo = app(EloquentCommentRepository::class);

    $comment1 = Comment::create(
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-batch-1',
        authorName: 'Author',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'First',
        sentiment: null,
        sentimentScore: null,
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    $comment2 = Comment::create(
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        socialAccountId: Uuid::fromString($this->accountId),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-batch-2',
        authorName: 'Author',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'Second',
        sentiment: null,
        sentimentScore: null,
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    $repo->create($comment1->releaseEvents());
    $repo->create($comment2->releaseEvents());

    $repo->markManyAsRead(
        Uuid::fromString($this->orgId),
        [(string) $comment1->id, (string) $comment2->id],
    );

    expect($repo->countUnread(Uuid::fromString($this->orgId)))->toBe(0);
});

it('uses cursor-based pagination', function () {
    $repo = app(EloquentCommentRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 1; $i <= 3; $i++) {
        $comment = Comment::create(
            organizationId: $orgId,
            contentId: Uuid::fromString($this->contentId),
            socialAccountId: Uuid::fromString($this->accountId),
            provider: SocialProvider::Instagram,
            externalCommentId: "ext-page-{$i}",
            authorName: 'Author',
            authorExternalId: null,
            authorProfileUrl: null,
            text: "Comment {$i}",
            sentiment: null,
            sentimentScore: null,
            isFromOwner: false,
            commentedAt: new DateTimeImmutable,
        );
        $repo->create($comment->releaseEvents());
    }

    $firstPage = $repo->findByOrganizationId($orgId, [], null, 2);
    expect($firstPage)->toHaveCount(2);

    $cursor = (string) $firstPage[1]->id;
    $secondPage = $repo->findByOrganizationId($orgId, [], $cursor, 2);
    expect($secondPage)->toHaveCount(1);
});
