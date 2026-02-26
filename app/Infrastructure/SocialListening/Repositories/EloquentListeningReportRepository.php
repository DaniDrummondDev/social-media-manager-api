<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningReport;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\ReportStatus;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;
use App\Infrastructure\SocialListening\Models\ListeningReportModel;
use DateTimeImmutable;

final class EloquentListeningReportRepository implements ListeningReportRepositoryInterface
{
    public function __construct(
        private readonly ListeningReportModel $model,
    ) {}

    public function create(ListeningReport $report): void
    {
        $this->model->newQuery()->create($this->toArray($report));
    }

    public function update(ListeningReport $report): void
    {
        $this->model->newQuery()
            ->where('id', (string) $report->id)
            ->update($this->toArray($report));
    }

    public function findById(Uuid $id): ?ListeningReport
    {
        /** @var ListeningReportModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<ListeningReport>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ListeningReportModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (ListeningReportModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ListeningReport $report): array
    {
        return [
            'id' => (string) $report->id,
            'organization_id' => (string) $report->organizationId,
            'query_ids' => $report->queryIds,
            'period_from' => $report->periodFrom->format('Y-m-d H:i:s'),
            'period_to' => $report->periodTo->format('Y-m-d H:i:s'),
            'total_mentions' => $report->totalMentions,
            'sentiment_breakdown' => $report->sentimentBreakdown->toArray(),
            'top_authors' => $report->topAuthors,
            'top_keywords' => $report->topKeywords,
            'platform_breakdown' => $report->platformBreakdown,
            'status' => $report->status->value,
            'file_path' => $report->filePath,
            'generated_at' => $report->generatedAt?->format('Y-m-d H:i:s'),
            'created_at' => $report->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(ListeningReportModel $model): ListeningReport
    {
        $createdAt = $model->getAttribute('created_at');
        $periodFrom = $model->getAttribute('period_from');
        $periodTo = $model->getAttribute('period_to');
        $generatedAt = $model->getAttribute('generated_at');
        $queryIds = $model->getAttribute('query_ids');
        $sentimentBreakdown = $model->getAttribute('sentiment_breakdown');
        $topAuthors = $model->getAttribute('top_authors');
        $topKeywords = $model->getAttribute('top_keywords');
        $platformBreakdown = $model->getAttribute('platform_breakdown');

        return ListeningReport::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            queryIds: is_array($queryIds) ? $queryIds : json_decode((string) $queryIds, true),
            periodFrom: new DateTimeImmutable($periodFrom->format('Y-m-d H:i:s')),
            periodTo: new DateTimeImmutable($periodTo->format('Y-m-d H:i:s')),
            totalMentions: (int) $model->getAttribute('total_mentions'),
            sentimentBreakdown: SentimentBreakdown::fromArray(
                is_array($sentimentBreakdown) ? $sentimentBreakdown : json_decode((string) $sentimentBreakdown, true),
            ),
            topAuthors: is_array($topAuthors) ? $topAuthors : json_decode((string) $topAuthors, true),
            topKeywords: is_array($topKeywords) ? $topKeywords : json_decode((string) $topKeywords, true),
            platformBreakdown: is_array($platformBreakdown) ? $platformBreakdown : json_decode((string) $platformBreakdown, true),
            status: ReportStatus::from($model->getAttribute('status')),
            filePath: $model->getAttribute('file_path'),
            generatedAt: $generatedAt ? new DateTimeImmutable($generatedAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
