<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\CalendarSuggestion;
use App\Domain\Shared\ValueObjects\Uuid;

interface CalendarSuggestionRepositoryInterface
{
    public function create(CalendarSuggestion $suggestion): void;

    public function update(CalendarSuggestion $suggestion): void;

    public function findById(Uuid $id): ?CalendarSuggestion;

    /**
     * @return array{items: array<CalendarSuggestion>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<CalendarSuggestion>
     */
    public function findExpired(): array;
}
