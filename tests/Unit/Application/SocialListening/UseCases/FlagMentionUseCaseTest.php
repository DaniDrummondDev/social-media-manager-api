<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\FlagMentionInput;
use App\Application\SocialListening\DTOs\MentionOutput;
use App\Application\SocialListening\Exceptions\MentionNotFoundException;
use App\Application\SocialListening\UseCases\FlagMentionUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\Sentiment;

beforeEach(function () {
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new FlagMentionUseCase(
        $this->mentionRepository,
        $this->eventDispatcher,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->mentionId = 'd4e5f6a7-b8c9-0123-def0-456789012345';
});

it('flags a mention successfully', function () {
    $mention = Mention::reconstitute(
        id: Uuid::fromString($this->mentionId),
        queryId: Uuid::fromString('b1c2d3e4-f5a6-7890-bcde-f12345678901'),
        organizationId: Uuid::fromString($this->orgId),
        platform: 'instagram',
        externalId: 'ext-123',
        authorUsername: 'johndoe',
        authorDisplayName: 'John Doe',
        authorFollowerCount: 1500,
        profileUrl: null,
        content: 'Negative content here',
        url: null,
        sentiment: Sentiment::Negative,
        sentimentScore: -0.75,
        reach: 1500,
        engagementCount: 42,
        isFlagged: false,
        isRead: false,
        publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
        detectedAt: new DateTimeImmutable('2024-01-15T10:05:00+00:00'),
    );

    $this->mentionRepository->shouldReceive('findById')->once()->andReturn($mention);
    $this->mentionRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new FlagMentionInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        mentionId: $this->mentionId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(MentionOutput::class)
        ->and($output->isFlagged)->toBeTrue();
});

it('throws when mention not found', function () {
    $this->mentionRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new FlagMentionInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        mentionId: $this->mentionId,
    );

    $this->useCase->execute($input);
})->throws(MentionNotFoundException::class);

it('throws when mention belongs to different organization', function () {
    $differentOrgId = 'c3d4e5f6-a7b8-9012-cdef-345678901234';

    $mention = Mention::reconstitute(
        id: Uuid::fromString($this->mentionId),
        queryId: Uuid::fromString('b1c2d3e4-f5a6-7890-bcde-f12345678901'),
        organizationId: Uuid::fromString($differentOrgId),
        platform: 'instagram',
        externalId: 'ext-123',
        authorUsername: 'johndoe',
        authorDisplayName: 'John Doe',
        authorFollowerCount: null,
        profileUrl: null,
        content: 'Some content',
        url: null,
        sentiment: null,
        sentimentScore: null,
        reach: 0,
        engagementCount: 0,
        isFlagged: false,
        isRead: false,
        publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
        detectedAt: new DateTimeImmutable('2024-01-15T10:05:00+00:00'),
    );

    $this->mentionRepository->shouldReceive('findById')->once()->andReturn($mention);

    $input = new FlagMentionInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        mentionId: $this->mentionId,
    );

    $this->useCase->execute($input);
})->throws(MentionNotFoundException::class);
