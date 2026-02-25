<?php

declare(strict_types=1);

use App\Application\Analytics\Contracts\SocialAnalyticsFactoryInterface;
use App\Application\Analytics\DTOs\SyncPostMetricsInput;
use App\Application\Analytics\UseCases\SyncPostMetricsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\Repositories\ContentMetricSnapshotRepositoryInterface;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Contracts\SocialAnalyticsInterface;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('creates new metric and snapshot when none exists', function () {
    $postId = Uuid::generate();
    $contentId = Uuid::generate();
    $accountId = Uuid::generate();
    $orgId = Uuid::generate();
    $userId = Uuid::generate();

    $post = ScheduledPost::reconstitute(
        id: $postId,
        organizationId: $orgId,
        contentId: $contentId,
        socialAccountId: $accountId,
        scheduledBy: $userId,
        scheduledAt: ScheduleTime::fromDateTimeImmutable(new DateTimeImmutable('-1 hour')),
        status: PublishingStatus::Published,
        publishedAt: new DateTimeImmutable('-1 hour'),
        externalPostId: 'ext-123',
        externalPostUrl: 'https://instagram.com/p/123',
        attempts: 1,
        maxAttempts: 3,
        lastAttemptedAt: new DateTimeImmutable('-1 hour'),
        lastError: null,
        nextRetryAt: null,
        dispatchedAt: new DateTimeImmutable('-1 hour'),
        createdAt: new DateTimeImmutable('-2 hours'),
        updatedAt: new DateTimeImmutable('-1 hour'),
    );

    $account = SocialAccount::reconstitute(
        id: $accountId,
        organizationId: $orgId,
        connectedBy: $userId,
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-001',
        username: '@test',
        displayName: 'Test',
        profilePictureUrl: null,
        credentials: OAuthCredentials::create(
            accessToken: EncryptedToken::fromEncrypted('token'),
            refreshToken: EncryptedToken::fromEncrypted('refresh'),
            expiresAt: new DateTimeImmutable('+30 days'),
            scopes: ['read'],
        ),
        status: \App\Domain\SocialAccount\ValueObjects\ConnectionStatus::Connected,
        lastSyncedAt: new DateTimeImmutable,
        connectedAt: new DateTimeImmutable,
        disconnectedAt: null,
        metadata: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
    );

    $postRepo = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $postRepo->shouldReceive('findById')->once()->andReturn($post);

    $accountRepo = Mockery::mock(SocialAccountRepositoryInterface::class);
    $accountRepo->shouldReceive('findById')->once()->andReturn($account);

    $adapter = Mockery::mock(SocialAnalyticsInterface::class);
    $adapter->shouldReceive('getPostMetrics')->once()->andReturn([
        'impressions' => 500,
        'reach' => 300,
        'likes' => 50,
        'comments' => 10,
        'shares' => 5,
        'saves' => 3,
        'clicks' => 20,
    ]);

    $factory = Mockery::mock(SocialAnalyticsFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($adapter);

    $metricRepo = Mockery::mock(ContentMetricRepositoryInterface::class);
    $metricRepo->shouldReceive('findByContentAndAccount')->once()->andReturnNull();
    $metricRepo->shouldReceive('upsert')->once();

    $snapshotRepo = Mockery::mock(ContentMetricSnapshotRepositoryInterface::class);
    $snapshotRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new SyncPostMetricsUseCase(
        $postRepo,
        $accountRepo,
        $metricRepo,
        $snapshotRepo,
        $factory,
        $dispatcher,
    );

    $useCase->execute(new SyncPostMetricsInput(
        scheduledPostId: (string) $postId,
    ));
});

it('skips when post has no external post id', function () {
    $postId = Uuid::generate();

    $post = ScheduledPost::reconstitute(
        id: $postId,
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::fromDateTimeImmutable(new DateTimeImmutable('-1 hour')),
        status: PublishingStatus::Pending,
        publishedAt: null,
        externalPostId: null,
        externalPostUrl: null,
        attempts: 0,
        maxAttempts: 3,
        lastAttemptedAt: null,
        lastError: null,
        nextRetryAt: null,
        dispatchedAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $postRepo = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $postRepo->shouldReceive('findById')->once()->andReturn($post);

    $accountRepo = Mockery::mock(SocialAccountRepositoryInterface::class);
    $metricRepo = Mockery::mock(ContentMetricRepositoryInterface::class);
    $snapshotRepo = Mockery::mock(ContentMetricSnapshotRepositoryInterface::class);
    $factory = Mockery::mock(SocialAnalyticsFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $accountRepo->shouldNotReceive('findById');
    $metricRepo->shouldNotReceive('upsert');

    $useCase = new SyncPostMetricsUseCase(
        $postRepo,
        $accountRepo,
        $metricRepo,
        $snapshotRepo,
        $factory,
        $dispatcher,
    );

    $useCase->execute(new SyncPostMetricsInput(
        scheduledPostId: (string) $postId,
    ));
});
