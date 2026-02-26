<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\CalendarSuggestion;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\CalendarItem;
use App\Domain\AIIntelligence\ValueObjects\SuggestionStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\CalendarSuggestionModel;
use DateTimeImmutable;

final class EloquentCalendarSuggestionRepository implements CalendarSuggestionRepositoryInterface
{
    public function __construct(
        private readonly CalendarSuggestionModel $model,
    ) {}

    public function create(CalendarSuggestion $suggestion): void
    {
        $this->model->newQuery()->create($this->toArray($suggestion));
    }

    public function update(CalendarSuggestion $suggestion): void
    {
        $this->model->newQuery()
            ->where('id', (string) $suggestion->id)
            ->update($this->toArray($suggestion));
    }

    public function findById(Uuid $id): ?CalendarSuggestion
    {
        /** @var CalendarSuggestionModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<CalendarSuggestion>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, CalendarSuggestionModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (CalendarSuggestionModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<CalendarSuggestion>
     */
    public function findExpired(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CalendarSuggestionModel> $records */
        $records = $this->model->newQuery()
            ->where('expires_at', '<=', now())
            ->whereNot('status', 'expired')
            ->get();

        return $records->map(fn (CalendarSuggestionModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(CalendarSuggestion $suggestion): array
    {
        return [
            'id' => (string) $suggestion->id,
            'organization_id' => (string) $suggestion->organizationId,
            'period_start' => $suggestion->periodStart->format('Y-m-d'),
            'period_end' => $suggestion->periodEnd->format('Y-m-d'),
            'suggestions' => array_map(fn (CalendarItem $item) => $item->toArray(), $suggestion->suggestions),
            'based_on' => $suggestion->basedOn,
            'status' => $suggestion->status->value,
            'accepted_items' => $suggestion->acceptedItems,
            'generated_at' => $suggestion->generatedAt->format('Y-m-d H:i:s'),
            'expires_at' => $suggestion->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $suggestion->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(CalendarSuggestionModel $model): CalendarSuggestion
    {
        $suggestions = $model->getAttribute('suggestions');
        $suggestionsArray = is_array($suggestions) ? $suggestions : json_decode((string) $suggestions, true);

        $acceptedItems = $model->getAttribute('accepted_items');
        $acceptedItemsArray = $acceptedItems !== null
            ? (is_array($acceptedItems) ? $acceptedItems : json_decode((string) $acceptedItems, true))
            : null;

        $basedOn = $model->getAttribute('based_on');
        $basedOnArray = is_array($basedOn) ? $basedOn : json_decode((string) $basedOn, true);

        $periodStart = $model->getAttribute('period_start');
        $periodEnd = $model->getAttribute('period_end');
        $generatedAt = $model->getAttribute('generated_at');
        $expiresAt = $model->getAttribute('expires_at');
        $createdAt = $model->getAttribute('created_at');

        return CalendarSuggestion::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            periodStart: new DateTimeImmutable($periodStart->format('Y-m-d')),
            periodEnd: new DateTimeImmutable($periodEnd->format('Y-m-d')),
            suggestions: array_map(fn (array $data) => CalendarItem::fromArray($data), $suggestionsArray),
            basedOn: $basedOnArray,
            status: SuggestionStatus::from($model->getAttribute('status')),
            acceptedItems: $acceptedItemsArray,
            generatedAt: new DateTimeImmutable($generatedAt->format('Y-m-d H:i:s')),
            expiresAt: new DateTimeImmutable($expiresAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
