<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\ProcessMentionsBatchInput;
use App\Application\SocialListening\Exceptions\ListeningQueryNotFoundException;
use App\Application\SocialListening\UseCases\ProcessMentionsBatchUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\QueryStatus;
use App\Domain\SocialListening\ValueObjects\QueryType;

beforeEach(function () {
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);
    $this->queryRepository = Mockery::mock(ListeningQueryRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new ProcessMentionsBatchUseCase(
        $this->mentionRepository,
        $this->queryRepository,
        $this->eventDispatcher,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->queryId = 'b1c2d3e4-f5a6-7890-bcde-f12345678901';
});

it('processes a mentions batch successfully', function () {
    $existingQuery = ListeningQuery::reconstitute(
        id: Uuid::fromString($this->queryId),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Brand Monitoring',
        type: QueryType::Keyword,
        value: 'brand',
        platforms: ['instagram'],
        status: QueryStatus::Active,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->queryRepository->shouldReceive('findById')->once()->andReturn($existingQuery);
    $this->mentionRepository->shouldReceive('existsByExternalId')->once()->andReturn(false);
    $this->mentionRepository->shouldReceive('createBatch')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new ProcessMentionsBatchInput(
        organizationId: $this->orgId,
        queryId: $this->queryId,
        mentionsData: [
            [
                'external_id' => 'ext-123',
                'platform' => 'instagram',
                'author_username' => 'johndoe',
                'author_display_name' => 'John Doe',
                'author_follower_count' => 1500,
                'profile_url' => 'https://instagram.com/johndoe',
                'content' => 'Great product!',
                'url' => 'https://instagram.com/p/123',
                'sentiment' => 'positive',
                'sentiment_score' => 0.85,
                'reach' => 1500,
                'engagement_count' => 42,
                'published_at' => '2024-01-15T10:00:00+00:00',
            ],
        ],
    );

    $result = $this->useCase->execute($input);

    expect($result)->toBe(1);
});

it('skips duplicate mentions', function () {
    $existingQuery = ListeningQuery::reconstitute(
        id: Uuid::fromString($this->queryId),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Brand Monitoring',
        type: QueryType::Keyword,
        value: 'brand',
        platforms: ['instagram'],
        status: QueryStatus::Active,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->queryRepository->shouldReceive('findById')->once()->andReturn($existingQuery);
    $this->mentionRepository->shouldReceive('existsByExternalId')->once()->andReturn(true);

    $input = new ProcessMentionsBatchInput(
        organizationId: $this->orgId,
        queryId: $this->queryId,
        mentionsData: [
            [
                'external_id' => 'ext-123',
                'platform' => 'instagram',
                'author_username' => 'johndoe',
                'author_display_name' => 'John Doe',
                'content' => 'Great product!',
                'published_at' => '2024-01-15T10:00:00+00:00',
            ],
        ],
    );

    $result = $this->useCase->execute($input);

    expect($result)->toBe(0);
});

it('throws when query not found', function () {
    $this->queryRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new ProcessMentionsBatchInput(
        organizationId: $this->orgId,
        queryId: $this->queryId,
        mentionsData: [],
    );

    $this->useCase->execute($input);
})->throws(ListeningQueryNotFoundException::class);

it('throws when query belongs to different organization', function () {
    $differentOrgId = 'c3d4e5f6-a7b8-9012-cdef-345678901234';

    $existingQuery = ListeningQuery::reconstitute(
        id: Uuid::fromString($this->queryId),
        organizationId: Uuid::fromString($differentOrgId),
        name: 'Brand Monitoring',
        type: QueryType::Keyword,
        value: 'brand',
        platforms: ['instagram'],
        status: QueryStatus::Active,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->queryRepository->shouldReceive('findById')->once()->andReturn($existingQuery);

    $input = new ProcessMentionsBatchInput(
        organizationId: $this->orgId,
        queryId: $this->queryId,
        mentionsData: [],
    );

    $this->useCase->execute($input);
})->throws(ListeningQueryNotFoundException::class);
