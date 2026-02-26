<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GenerateCalendarSuggestionsInput;
use App\Application\AIIntelligence\DTOs\GenerateCalendarSuggestionsOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\CalendarSuggestion;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GenerateCalendarSuggestionsUseCase
{
    public function __construct(
        private readonly CalendarSuggestionRepositoryInterface $suggestionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(GenerateCalendarSuggestionsInput $input): GenerateCalendarSuggestionsOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $suggestion = CalendarSuggestion::create(
            organizationId: $organizationId,
            periodStart: new DateTimeImmutable($input->periodStart),
            periodEnd: new DateTimeImmutable($input->periodEnd),
            userId: $input->userId,
        );

        $this->suggestionRepository->create($suggestion);
        $this->eventDispatcher->dispatch(...$suggestion->domainEvents);

        return new GenerateCalendarSuggestionsOutput(
            suggestionId: (string) $suggestion->id,
            status: $suggestion->status->value,
            message: 'Calendar suggestions are being generated. Check back shortly.',
        );
    }
}
