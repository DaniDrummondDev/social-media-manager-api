<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\GetMentionDetailsInput;
use App\Application\SocialListening\DTOs\MentionOutput;
use App\Application\SocialListening\Exceptions\MentionNotFoundException;
use App\Application\SocialListening\UseCases\GetMentionDetailsUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\Sentiment;

beforeEach(function () {
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);

    $this->useCase = new GetMentionDetailsUseCase(
        $this->mentionRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->mentionId = 'd4e5f6a7-b8c9-0123-def0-456789012345';
});

it('gets mention details successfully', function () {
    $mention = Mention::reconstitute(
        id: Uuid::fromString($this->mentionId),
        queryId: Uuid::fromString('b1c2d3e4-f5a6-7890-bcde-f12345678901'),
        organizationId: Uuid::fromString($this->orgId),
        platform: 'instagram',
        externalId: 'ext-123',
        authorUsername: 'johndoe',
        authorDisplayName: 'John Doe',
        authorFollowerCount: 1500,
        profileUrl: 'https://instagram.com/johndoe',
        content: 'Great product!',
        url: 'https://instagram.com/p/123',
        sentiment: Sentiment::Positive,
        sentimentScore: 0.85,
        reach: 1500,
        engagementCount: 42,
        isFlagged: false,
        isRead: false,
        publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
        detectedAt: new DateTimeImmutable('2024-01-15T10:05:00+00:00'),
    );

    $this->mentionRepository->shouldReceive('findById')->once()->andReturn($mention);

    $input = new GetMentionDetailsInput(
        organizationId: $this->orgId,
        mentionId: $this->mentionId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(MentionOutput::class)
        ->and($output->authorUsername)->toBe('johndoe')
        ->and($output->content)->toBe('Great product!')
        ->and($output->sentiment)->toBe('positive')
        ->and($output->reach)->toBe(1500);
});

it('throws when mention not found', function () {
    $this->mentionRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new GetMentionDetailsInput(
        organizationId: $this->orgId,
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
        authorFollowerCount: 1500,
        profileUrl: null,
        content: 'Great product!',
        url: null,
        sentiment: Sentiment::Positive,
        sentimentScore: 0.85,
        reach: 1500,
        engagementCount: 42,
        isFlagged: false,
        isRead: false,
        publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
        detectedAt: new DateTimeImmutable('2024-01-15T10:05:00+00:00'),
    );

    $this->mentionRepository->shouldReceive('findById')->once()->andReturn($mention);

    $input = new GetMentionDetailsInput(
        organizationId: $this->orgId,
        mentionId: $this->mentionId,
    );

    $this->useCase->execute($input);
})->throws(MentionNotFoundException::class);
