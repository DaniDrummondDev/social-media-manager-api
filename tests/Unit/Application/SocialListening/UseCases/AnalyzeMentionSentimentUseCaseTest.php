<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\SentimentAnalyzerInterface;
use App\Application\Shared\DTOs\SentimentResult;
use App\Application\SocialListening\Exceptions\MentionNotFoundException;
use App\Application\SocialListening\UseCases\AnalyzeMentionSentimentUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;

beforeEach(function () {
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);
    $this->sentimentAnalyzer = Mockery::mock(SentimentAnalyzerInterface::class);

    $this->useCase = new AnalyzeMentionSentimentUseCase(
        $this->mentionRepository,
        $this->sentimentAnalyzer,
    );

    $this->mentionId = 'd4e5f6a7-b8c9-0123-def0-456789012345';
});

it('analyzes mention sentiment successfully', function () {
    $mention = Mention::reconstitute(
        id: Uuid::fromString($this->mentionId),
        queryId: Uuid::fromString('b1c2d3e4-f5a6-7890-bcde-f12345678901'),
        organizationId: Uuid::fromString('f47ac10b-58cc-4372-a567-0e02b2c3d479'),
        platform: 'instagram',
        externalId: 'ext-123',
        authorUsername: 'johndoe',
        authorDisplayName: 'John Doe',
        authorFollowerCount: 1500,
        profileUrl: null,
        content: 'This is an amazing product!',
        url: null,
        sentiment: null,
        sentimentScore: null,
        reach: 1500,
        engagementCount: 42,
        isFlagged: false,
        isRead: false,
        publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
        detectedAt: new DateTimeImmutable('2024-01-15T10:05:00+00:00'),
    );

    $sentimentResult = new SentimentResult(
        sentiment: 'positive',
        score: 0.92,
    );

    $this->mentionRepository->shouldReceive('findById')->once()->andReturn($mention);
    $this->sentimentAnalyzer->shouldReceive('analyze')
        ->once()
        ->with('This is an amazing product!')
        ->andReturn($sentimentResult);
    $this->mentionRepository->shouldReceive('update')->once();

    $this->useCase->execute($this->mentionId);

    expect(true)->toBeTrue();
});

it('throws when mention not found', function () {
    $this->mentionRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute($this->mentionId);
})->throws(MentionNotFoundException::class);
