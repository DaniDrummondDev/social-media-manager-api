<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\SchedulePostInput;
use App\Application\Publishing\UseCases\SchedulePostUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Exceptions\PublishingNotAllowedException;
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

    $this->useCase = new SchedulePostUseCase(
        $this->scheduledPostRepository,
        $this->contentRepository,
        $this->socialAccountRepository,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->contentId = (string) Uuid::generate();
    $this->accountId = (string) Uuid::generate();
    $this->scheduledAt = (new DateTimeImmutable('+1 day'))->format('c');
});

function makeContent(string $id, string $orgId, ContentStatus $status = ContentStatus::Ready): Content
{
    return Content::reconstitute(
        id: Uuid::fromString($id),
        organizationId: Uuid::fromString($orgId),
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Test Content',
        body: 'Test body',
        hashtags: [],
        status: $status,
        aiGenerationId: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
    );
}

function makeSocialAccount(string $id, string $orgId, ConnectionStatus $status = ConnectionStatus::Connected): SocialAccount
{
    return SocialAccount::reconstitute(
        id: Uuid::fromString($id),
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'provider-123',
        username: 'testuser',
        displayName: 'Test User',
        profilePictureUrl: null,
        credentials: OAuthCredentials::create(
            accessToken: EncryptedToken::fromEncrypted('token'),
            refreshToken: EncryptedToken::fromEncrypted('refresh'),
            expiresAt: new DateTimeImmutable('+1 hour'),
        ),
        status: $status,
        lastSyncedAt: null,
        connectedAt: new DateTimeImmutable,
        disconnectedAt: null,
        metadata: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
    );
}

it('schedules post successfully for one account', function () {
    $content = makeContent($this->contentId, $this->orgId);
    $account = makeSocialAccount($this->accountId, $this->orgId);

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->scheduledPostRepository->shouldReceive('existsByContentAndAccount')->once()->andReturn(false);
    $this->scheduledPostRepository->shouldReceive('create')->once();
    $this->contentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new SchedulePostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
        scheduledAt: $this->scheduledAt,
    ));

    expect($output->contentId)->toBe($this->contentId)
        ->and($output->scheduledPosts)->toHaveCount(1)
        ->and($output->scheduledPosts[0]->status)->toBe('pending');
});

it('schedules post for multiple accounts', function () {
    $accountId2 = (string) Uuid::generate();
    $content = makeContent($this->contentId, $this->orgId);
    $account1 = makeSocialAccount($this->accountId, $this->orgId);
    $account2 = makeSocialAccount($accountId2, $this->orgId);

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->socialAccountRepository->shouldReceive('findById')->twice()->andReturn($account1, $account2);
    $this->scheduledPostRepository->shouldReceive('existsByContentAndAccount')->twice()->andReturn(false);
    $this->scheduledPostRepository->shouldReceive('create')->twice();
    $this->contentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new SchedulePostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId, $accountId2],
        scheduledAt: $this->scheduledAt,
    ));

    expect($output->scheduledPosts)->toHaveCount(2);
});

it('throws when content not found', function () {
    $this->contentRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new SchedulePostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
        scheduledAt: $this->scheduledAt,
    ));
})->throws(\App\Domain\Campaign\Exceptions\ContentNotFoundException::class);

it('throws when content belongs to different org', function () {
    $otherOrgId = (string) Uuid::generate();
    $content = makeContent($this->contentId, $otherOrgId);

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);

    $this->useCase->execute(new SchedulePostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
        scheduledAt: $this->scheduledAt,
    ));
})->throws(\App\Domain\Campaign\Exceptions\ContentNotFoundException::class);

it('throws when social account is not connected', function () {
    $content = makeContent($this->contentId, $this->orgId);
    $account = makeSocialAccount($this->accountId, $this->orgId, ConnectionStatus::Disconnected);

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $this->useCase->execute(new SchedulePostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
        scheduledAt: $this->scheduledAt,
    ));
})->throws(PublishingNotAllowedException::class);

it('throws when content already scheduled for account', function () {
    $content = makeContent($this->contentId, $this->orgId);
    $account = makeSocialAccount($this->accountId, $this->orgId);

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->scheduledPostRepository->shouldReceive('existsByContentAndAccount')->once()->andReturn(true);

    $this->useCase->execute(new SchedulePostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: $this->contentId,
        socialAccountIds: [$this->accountId],
        scheduledAt: $this->scheduledAt,
    ));
})->throws(PublishingNotAllowedException::class);
