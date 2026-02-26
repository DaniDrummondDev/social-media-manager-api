<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\CalendarSuggestionOutput;
use App\Application\AIIntelligence\DTOs\GetCalendarSuggestionInput;
use App\Application\AIIntelligence\Exceptions\CalendarSuggestionNotFoundException;
use App\Application\AIIntelligence\UseCases\GetCalendarSuggestionUseCase;
use App\Domain\AIIntelligence\Entities\CalendarSuggestion;
use App\Domain\AIIntelligence\ValueObjects\SuggestionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->suggestionRepository = Mockery::mock(
        \App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface::class,
    );

    $this->useCase = new GetCalendarSuggestionUseCase($this->suggestionRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns CalendarSuggestionOutput when found', function () {
    $now = new DateTimeImmutable;
    $suggestionId = Uuid::generate();

    $suggestion = CalendarSuggestion::reconstitute(
        id: $suggestionId,
        organizationId: Uuid::fromString($this->orgId),
        periodStart: new DateTimeImmutable('2026-03-01'),
        periodEnd: new DateTimeImmutable('2026-03-07'),
        suggestions: [],
        basedOn: ['analytics' => true],
        status: SuggestionStatus::Generated,
        acceptedItems: null,
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
    );

    $this->suggestionRepository->shouldReceive('findById')->once()->andReturn($suggestion);

    $input = new GetCalendarSuggestionInput(
        organizationId: $this->orgId,
        suggestionId: (string) $suggestionId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(CalendarSuggestionOutput::class)
        ->and($output->id)->toBe((string) $suggestionId)
        ->and($output->status)->toBe('generated')
        ->and($output->basedOn)->toBe(['analytics' => true]);
});

it('throws CalendarSuggestionNotFoundException when not found', function () {
    $this->suggestionRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new GetCalendarSuggestionInput(
        organizationId: $this->orgId,
        suggestionId: (string) Uuid::generate(),
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

    $input = new GetCalendarSuggestionInput(
        organizationId: $this->orgId,
        suggestionId: (string) $suggestion->id,
    );

    $this->useCase->execute($input);
})->throws(CalendarSuggestionNotFoundException::class);
