<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\AcceptCalendarItemsInput;
use App\Application\AIIntelligence\DTOs\AcceptCalendarItemsOutput;
use App\Application\AIIntelligence\Exceptions\CalendarSuggestionNotFoundException;
use App\Application\AIIntelligence\UseCases\AcceptCalendarItemsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\CalendarSuggestion;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\CalendarItem;
use App\Domain\AIIntelligence\ValueObjects\SuggestionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->suggestionRepository = Mockery::mock(CalendarSuggestionRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new AcceptCalendarItemsUseCase(
        $this->suggestionRepository,
        $this->eventDispatcher,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('accepts items and returns AcceptCalendarItemsOutput', function () {
    $now = new DateTimeImmutable;
    $suggestionId = Uuid::generate();
    $items = [
        CalendarItem::create('2026-03-01', ['topic-1'], 'post', ['instagram'], 'Reason 1', 1),
        CalendarItem::create('2026-03-02', ['topic-2'], 'reel', ['tiktok'], 'Reason 2', 2),
    ];

    $suggestion = CalendarSuggestion::reconstitute(
        id: $suggestionId,
        organizationId: Uuid::fromString($this->orgId),
        periodStart: new DateTimeImmutable('2026-03-01'),
        periodEnd: new DateTimeImmutable('2026-03-07'),
        suggestions: $items,
        basedOn: [],
        status: SuggestionStatus::Generated,
        acceptedItems: null,
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
    );

    $this->suggestionRepository->shouldReceive('findById')->once()->andReturn($suggestion);
    $this->suggestionRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new AcceptCalendarItemsInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        suggestionId: (string) $suggestionId,
        acceptedIndexes: [0],
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(AcceptCalendarItemsOutput::class)
        ->and($output->status)->toBe('accepted')
        ->and($output->acceptedCount)->toBe(1)
        ->and($output->totalCount)->toBe(2);
});

it('throws CalendarSuggestionNotFoundException when not found', function () {
    $this->suggestionRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new AcceptCalendarItemsInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        suggestionId: (string) Uuid::generate(),
        acceptedIndexes: [0],
    );

    $this->useCase->execute($input);
})->throws(CalendarSuggestionNotFoundException::class);

it('throws CalendarSuggestionNotFoundException when organization mismatch', function () {
    $now = new DateTimeImmutable;
    $differentOrgId = Uuid::generate();

    $suggestion = CalendarSuggestion::reconstitute(
        id: Uuid::generate(),
        organizationId: $differentOrgId,
        periodStart: new DateTimeImmutable('2026-03-01'),
        periodEnd: new DateTimeImmutable('2026-03-07'),
        suggestions: [],
        basedOn: [],
        status: SuggestionStatus::Generated,
        acceptedItems: null,
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
    );

    $this->suggestionRepository->shouldReceive('findById')->once()->andReturn($suggestion);

    $input = new AcceptCalendarItemsInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        suggestionId: (string) $suggestion->id,
        acceptedIndexes: [0],
    );

    $this->useCase->execute($input);
})->throws(CalendarSuggestionNotFoundException::class);
