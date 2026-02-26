<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\CalendarSuggestionListOutput;
use App\Application\AIIntelligence\DTOs\ListCalendarSuggestionsInput;
use App\Application\AIIntelligence\UseCases\ListCalendarSuggestionsUseCase;
use App\Domain\AIIntelligence\Entities\CalendarSuggestion;
use App\Domain\AIIntelligence\ValueObjects\SuggestionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->suggestionRepository = Mockery::mock(
        \App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface::class,
    );

    $this->useCase = new ListCalendarSuggestionsUseCase($this->suggestionRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns items and next_cursor when suggestions exist', function () {
    $now = new DateTimeImmutable;
    $suggestion = CalendarSuggestion::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
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

    $this->suggestionRepository
        ->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn(['items' => [$suggestion], 'next_cursor' => 'cursor-abc']);

    $input = new ListCalendarSuggestionsInput(
        organizationId: $this->orgId,
        cursor: null,
        limit: 20,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toBeInstanceOf(CalendarSuggestionListOutput::class)
        ->and($result['next_cursor'])->toBe('cursor-abc');
});

it('returns empty items when no suggestions', function () {
    $this->suggestionRepository
        ->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn(['items' => [], 'next_cursor' => null]);

    $input = new ListCalendarSuggestionsInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toBeEmpty()
        ->and($result['next_cursor'])->toBeNull();
});
