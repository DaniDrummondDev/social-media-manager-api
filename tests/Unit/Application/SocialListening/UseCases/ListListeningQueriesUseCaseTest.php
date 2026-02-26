<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\ListeningQueryOutput;
use App\Application\SocialListening\DTOs\ListListeningQueriesInput;
use App\Application\SocialListening\UseCases\ListListeningQueriesUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\QueryStatus;
use App\Domain\SocialListening\ValueObjects\QueryType;

beforeEach(function () {
    $this->queryRepository = Mockery::mock(ListeningQueryRepositoryInterface::class);

    $this->useCase = new ListListeningQueriesUseCase(
        $this->queryRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('lists listening queries successfully', function () {
    $query1 = ListeningQuery::reconstitute(
        id: Uuid::fromString('b1c2d3e4-f5a6-7890-bcde-f12345678901'),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Brand Monitoring',
        type: QueryType::Keyword,
        value: 'brand',
        platforms: ['instagram'],
        status: QueryStatus::Active,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $query2 = ListeningQuery::reconstitute(
        id: Uuid::fromString('c2d3e4f5-a6b7-8901-cdef-234567890123'),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Competitor Watch',
        type: QueryType::Competitor,
        value: 'competitor',
        platforms: ['tiktok'],
        status: QueryStatus::Paused,
        createdAt: new DateTimeImmutable('2024-01-02'),
        updatedAt: new DateTimeImmutable('2024-01-02'),
    );

    $this->queryRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [$query1, $query2],
            'next_cursor' => null,
        ]);

    $input = new ListListeningQueriesInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result)->toBeArray()
        ->and($result['items'])->toHaveCount(2)
        ->and($result['items'][0])->toBeInstanceOf(ListeningQueryOutput::class)
        ->and($result['items'][0]->name)->toBe('Brand Monitoring')
        ->and($result['items'][1]->name)->toBe('Competitor Watch')
        ->and($result['next_cursor'])->toBeNull();
});

it('returns empty list when no queries exist', function () {
    $this->queryRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [],
            'next_cursor' => null,
        ]);

    $input = new ListListeningQueriesInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toBeEmpty()
        ->and($result['next_cursor'])->toBeNull();
});
