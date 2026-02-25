<?php

declare(strict_types=1);

use App\Application\Publishing\Contracts\SocialPublisherFactoryInterface;
use App\Application\Publishing\DTOs\ProcessScheduledPostInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Publishing\UseCases\ProcessScheduledPostUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Contracts\SocialPublisherInterface;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\ConnectionStatus;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $this->socialAccountRepository = Mockery::mock(SocialAccountRepositoryInterface::class);
    $this->contentRepository = Mockery::mock(ContentRepositoryInterface::class);
    $this->publisher = Mockery::mock(SocialPublisherInterface::class);
    $this->publisherFactory = Mockery::mock(SocialPublisherFactoryInterface::class);
    $this->publisherFactory->shouldReceive('make')->andReturn($this->publisher);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new ProcessScheduledPostUseCase(
        $this->scheduledPostRepository,
        $this->socialAccountRepository,
        $this->contentRepository,
        $this->publisherFactory,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->postId = (string) Uuid::generate();
    $this->contentId = Uuid::generate();
    $this->accountId = Uuid::generate();
});

function makeDispatchedPost(string $id, Uuid $contentId, Uuid $accountId, string $orgId): ScheduledPost
{
    return ScheduledPost::reconstitute(
        id: Uuid::fromString($id),
        organizationId: Uuid::fromString($orgId),
        contentId: $contentId,
        socialAccountId: $accountId,
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::forImmediate(),
        status: PublishingStatus::Dispatched,
        publishedAt: null,
        externalPostId: null,
        externalPostUrl: null,
        attempts: 0,
        maxAttempts: 3,
        lastAttemptedAt: null,
        lastError: null,
        nextRetryAt: null,
        dispatchedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

function makeProcessContent(Uuid $id, string $orgId): Content
{
    return Content::reconstitute(
        id: $id,
        organizationId: Uuid::fromString($orgId),
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Test Content',
        body: 'Test body',
        hashtags: [],
        status: ContentStatus::Scheduled,
        aiGenerationId: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
    );
}

function makeProcessAccount(Uuid $id, string $orgId): SocialAccount
{
    return SocialAccount::reconstitute(
        id: $id,
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'p-123',
        username: 'testuser',
        displayName: null,
        profilePictureUrl: null,
        credentials: OAuthCredentials::create(EncryptedToken::fromEncrypted('token'), EncryptedToken::fromEncrypted('refresh'), new DateTimeImmutable('+1 hour')),
        status: ConnectionStatus::Connected,
        lastSyncedAt: null,
        connectedAt: new DateTimeImmutable,
        disconnectedAt: null,
        metadata: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
    );
}

it('processes and publishes successfully', function () {
    $post = makeDispatchedPost($this->postId, $this->contentId, $this->accountId, $this->orgId);
    $content = makeProcessContent($this->contentId, $this->orgId);
    $account = makeProcessAccount($this->accountId, $this->orgId);

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);
    $this->scheduledPostRepository->shouldReceive('update')->times(2);
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->contentRepository->shouldReceive('findById')->twice()->andReturn($content);

    $this->publisher->shouldReceive('publish')->once()->andReturn([
        'external_post_id' => 'ext-123',
        'external_post_url' => 'https://instagram.com/p/ext-123',
    ]);

    // After publishing, check if all posts for content are done
    $publishedPost = ScheduledPost::reconstitute(
        id: Uuid::fromString($this->postId),
        organizationId: Uuid::fromString($this->orgId),
        contentId: $this->contentId,
        socialAccountId: $this->accountId,
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::forImmediate(),
        status: PublishingStatus::Published,
        publishedAt: new DateTimeImmutable,
        externalPostId: 'ext-123',
        externalPostUrl: 'https://instagram.com/p/ext-123',
        attempts: 1,
        maxAttempts: 3,
        lastAttemptedAt: new DateTimeImmutable,
        lastError: null,
        nextRetryAt: null,
        dispatchedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->scheduledPostRepository->shouldReceive('findByContentId')->once()->andReturn([$publishedPost]);
    $this->contentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new ProcessScheduledPostInput(
        scheduledPostId: $this->postId,
    ));

    expect($output->status)->toBe('published')
        ->and($output->externalPostId)->toBe('ext-123');
});

it('marks as failed on transient error', function () {
    $post = makeDispatchedPost($this->postId, $this->contentId, $this->accountId, $this->orgId);
    $content = makeProcessContent($this->contentId, $this->orgId);
    $account = makeProcessAccount($this->accountId, $this->orgId);

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);
    $this->scheduledPostRepository->shouldReceive('update')->times(2);
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);

    $this->publisher->shouldReceive('publish')->once()->andThrow(
        new RuntimeException('API temporarily unavailable'),
    );

    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new ProcessScheduledPostInput(
        scheduledPostId: $this->postId,
    ));

    expect($output->status)->toBe('failed')
        ->and($output->lastError)->not->toBeNull()
        ->and($output->lastError['is_permanent'])->toBeFalse();
});

it('throws when post not in dispatched status', function () {
    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new ProcessScheduledPostInput(
        scheduledPostId: $this->postId,
    ));
})->throws(ScheduledPostNotFoundException::class);
