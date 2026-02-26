<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\ListeningQueryOutput;
use App\Application\SocialListening\DTOs\PauseListeningQueryInput;
use App\Application\SocialListening\Exceptions\ListeningQueryNotFoundException;
use App\Application\SocialListening\UseCases\PauseListeningQueryUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\QueryStatus;
use App\Domain\SocialListening\ValueObjects\QueryType;

beforeEach(function () {
    $this->queryRepository = Mockery::mock(ListeningQueryRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new PauseListeningQueryUseCase(
        $this->queryRepository,
        $this->eventDispatcher,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->queryId = 'b1c2d3e4-f5a6-7890-bcde-f12345678901';
});

it('pauses a listening query successfully', function () {
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
    $this->queryRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new PauseListeningQueryInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        queryId: $this->queryId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningQueryOutput::class)
        ->and($output->isActive)->toBeFalse();
});

it('throws when query not found', function () {
    $this->queryRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new PauseListeningQueryInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        queryId: $this->queryId,
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

    $input = new PauseListeningQueryInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        queryId: $this->queryId,
    );

    $this->useCase->execute($input);
})->throws(ListeningQueryNotFoundException::class);
