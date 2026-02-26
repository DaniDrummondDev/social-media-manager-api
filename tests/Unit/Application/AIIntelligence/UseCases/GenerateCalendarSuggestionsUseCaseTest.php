<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GenerateCalendarSuggestionsInput;
use App\Application\AIIntelligence\DTOs\GenerateCalendarSuggestionsOutput;
use App\Application\AIIntelligence\UseCases\GenerateCalendarSuggestionsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;

beforeEach(function () {
    $this->suggestionRepository = Mockery::mock(CalendarSuggestionRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new GenerateCalendarSuggestionsUseCase(
        $this->suggestionRepository,
        $this->eventDispatcher,
    );
});

it('creates suggestion and returns GenerateCalendarSuggestionsOutput with status generating', function () {
    $this->suggestionRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new GenerateCalendarSuggestionsInput(
        organizationId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        periodStart: '2026-03-01',
        periodEnd: '2026-03-07',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(GenerateCalendarSuggestionsOutput::class)
        ->and($output->status)->toBe('generating')
        ->and($output->suggestionId)->toBeString()
        ->and($output->message)->toBeString();
});
