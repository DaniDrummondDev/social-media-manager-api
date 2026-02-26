<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\CalendarSuggestionListOutput;
use App\Application\AIIntelligence\DTOs\ListCalendarSuggestionsInput;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListCalendarSuggestionsUseCase
{
    public function __construct(
        private readonly CalendarSuggestionRepositoryInterface $suggestionRepository,
    ) {}

    /**
     * @return array{items: array<CalendarSuggestionListOutput>, next_cursor: ?string}
     */
    public function execute(ListCalendarSuggestionsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->suggestionRepository->findByOrganizationId(
            organizationId: $organizationId,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($suggestion) => CalendarSuggestionListOutput::fromEntity($suggestion),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
