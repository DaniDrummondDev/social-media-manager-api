<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\ListMentionsInput;
use App\Application\SocialListening\DTOs\MentionOutput;
use App\Application\SocialListening\UseCases\ListMentionsUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\Sentiment;

beforeEach(function () {
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);

    $this->useCase = new ListMentionsUseCase(
        $this->mentionRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('lists mentions successfully', function () {
    $mention = Mention::reconstitute(
        id: Uuid::fromString('d4e5f6a7-b8c9-0123-def0-456789012345'),
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

    $this->mentionRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [$mention],
            'next_cursor' => null,
        ]);

    $input = new ListMentionsInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result)->toBeArray()
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toBeInstanceOf(MentionOutput::class)
        ->and($result['items'][0]->authorUsername)->toBe('johndoe')
        ->and($result['items'][0]->sentiment)->toBe('positive')
        ->and($result['next_cursor'])->toBeNull();
});

it('returns empty list when no mentions exist', function () {
    $this->mentionRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [],
            'next_cursor' => null,
        ]);

    $input = new ListMentionsInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toBeEmpty()
        ->and($result['next_cursor'])->toBeNull();
});
