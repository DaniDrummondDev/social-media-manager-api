<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\PublishNowInput;
use App\Application\Publishing\UseCases\PublishNowUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\ConnectionStatus;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $this->contentRepository = Mockery::mock(ContentRepositoryInterface::class);
    $this->socialAccountRepository = Mockery::mock(SocialAccountRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new PublishNowUseCase(
        $this->scheduledPostRepository,
        $this->contentRepository,
        $this->socialAccountRepository,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->contentId = (string) Uuid::generate();
    $this->accountId = (string) Uuid::generate();
});

it('publishes immediately with dispatched status', function () {
    $content = Content::reconstitute(
        id: Uuid::fromString($this->contentId),
        organizationId: Uuid::fromString($this->orgId),
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Test',
        body: 'Body',
        hashtags: [],
        status: ContentStatus::Ready,
        aiGenerationId: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
    );

    $account = SocialAccount::reconstitute(
        id: Uuid::fromString($this->accountId),
        organizationId: Uuid::fromString($this->orgId),
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

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->scheduledPostRepository->shouldReceive('existsByContentAndAccount')->once()->andReturn(false);
    $this->scheduledPostRepository->shouldReceive('create')->once();
    $this->contentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new PublishNowInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
    ));

    expect($output->contentId)->toBe($this->contentId)
        ->and($output->scheduledPosts)->toHaveCount(1)
        ->and($output->scheduledPosts[0]->status)->toBe('dispatched');
});

it('throws when content not found', function () {
    $this->contentRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new PublishNowInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
    ));
})->throws(\App\Domain\Campaign\Exceptions\ContentNotFoundException::class);

it('throws when content status is not ready', function () {
    $content = Content::reconstitute(
        id: Uuid::fromString($this->contentId),
        organizationId: Uuid::fromString($this->orgId),
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Test',
        body: 'Body',
        hashtags: [],
        status: ContentStatus::Draft,
        aiGenerationId: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
    );

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);

    $this->useCase->execute(new PublishNowInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
    ));
})->throws(\App\Domain\Publishing\Exceptions\PublishingNotAllowedException::class);
