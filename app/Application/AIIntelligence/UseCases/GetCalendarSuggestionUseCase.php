<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\CalendarSuggestionOutput;
use App\Application\AIIntelligence\DTOs\GetCalendarSuggestionInput;
use App\Application\AIIntelligence\Exceptions\CalendarSuggestionNotFoundException;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetCalendarSuggestionUseCase
{
    public function __construct(
        private readonly CalendarSuggestionRepositoryInterface $suggestionRepository,
    ) {}

    public function execute(GetCalendarSuggestionInput $input): CalendarSuggestionOutput
    {
        $suggestionId = Uuid::fromString($input->suggestionId);

        $suggestion = $this->suggestionRepository->findById($suggestionId);

        if ($suggestion === null) {
            throw new CalendarSuggestionNotFoundException;
        }

        if ((string) $suggestion->organizationId !== $input->organizationId) {
            throw new CalendarSuggestionNotFoundException;
        }

        return CalendarSuggestionOutput::fromEntity($suggestion);
    }
}
