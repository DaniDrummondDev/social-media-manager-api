<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\AcceptCalendarItemsInput;
use App\Application\AIIntelligence\DTOs\AcceptCalendarItemsOutput;
use App\Application\AIIntelligence\Exceptions\CalendarSuggestionNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class AcceptCalendarItemsUseCase
{
    public function __construct(
        private readonly CalendarSuggestionRepositoryInterface $suggestionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(AcceptCalendarItemsInput $input): AcceptCalendarItemsOutput
    {
        $suggestionId = Uuid::fromString($input->suggestionId);

        $suggestion = $this->suggestionRepository->findById($suggestionId);

        if ($suggestion === null) {
            throw new CalendarSuggestionNotFoundException;
        }

        if ((string) $suggestion->organizationId !== $input->organizationId) {
            throw new CalendarSuggestionNotFoundException;
        }

        $updated = $suggestion->acceptItems($input->acceptedIndexes, $input->userId);

        $this->suggestionRepository->update($updated);
        $this->eventDispatcher->dispatch(...$updated->domainEvents);

        return new AcceptCalendarItemsOutput(
            id: (string) $updated->id,
            status: $updated->status->value,
            acceptedCount: count($input->acceptedIndexes),
            totalCount: count($updated->suggestions),
        );
    }
}
