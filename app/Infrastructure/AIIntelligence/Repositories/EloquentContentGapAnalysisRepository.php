<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\GapAnalysisStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\ContentGapAnalysisModel;
use DateTimeImmutable;

final class EloquentContentGapAnalysisRepository implements ContentGapAnalysisRepositoryInterface
{
    public function __construct(
        private readonly ContentGapAnalysisModel $model,
    ) {}

    public function create(ContentGapAnalysis $analysis): void
    {
        $this->model->newQuery()->create($this->toArray($analysis));
    }

    public function update(ContentGapAnalysis $analysis): void
    {
        $this->model->newQuery()
            ->where('id', (string) $analysis->id)
            ->update($this->toArray($analysis));
    }

    public function findById(Uuid $id): ?ContentGapAnalysis
    {
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<ContentGapAnalysis>, next_cursor: ?string}
     */
    public function findByOrganization(
        Uuid $organizationId,
        ?string $cursor = null,
        int $limit = 20,
    ): array {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        $records = $query->get();
        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn ($r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ContentGapAnalysis $analysis): array
    {
        return [
            'id' => (string) $analysis->id,
            'organization_id' => (string) $analysis->organizationId,
            'competitor_query_ids' => $analysis->competitorQueryIds,
            'analysis_period_start' => $analysis->analysisPeriodStart->format('Y-m-d H:i:s'),
            'analysis_period_end' => $analysis->analysisPeriodEnd->format('Y-m-d H:i:s'),
            'our_topics' => $analysis->ourTopics,
            'competitor_topics' => $analysis->competitorTopics,
            'gaps' => $analysis->gaps,
            'opportunities' => $analysis->opportunities,
            'status' => $analysis->status->value,
            'generated_at' => $analysis->generatedAt->format('Y-m-d H:i:s'),
            'expires_at' => $analysis->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $analysis->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(ContentGapAnalysisModel $model): ContentGapAnalysis
    {
        $competitorQueryIds = $model->getAttribute('competitor_query_ids');
        $ourTopics = $model->getAttribute('our_topics');
        $competitorTopics = $model->getAttribute('competitor_topics');
        $gaps = $model->getAttribute('gaps');
        $opportunities = $model->getAttribute('opportunities');

        return ContentGapAnalysis::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            competitorQueryIds: is_array($competitorQueryIds) ? $competitorQueryIds : json_decode((string) $competitorQueryIds, true),
            analysisPeriodStart: new DateTimeImmutable($model->getAttribute('analysis_period_start')->format('Y-m-d H:i:s')),
            analysisPeriodEnd: new DateTimeImmutable($model->getAttribute('analysis_period_end')->format('Y-m-d H:i:s')),
            ourTopics: is_array($ourTopics) ? $ourTopics : json_decode((string) $ourTopics, true),
            competitorTopics: is_array($competitorTopics) ? $competitorTopics : json_decode((string) $competitorTopics, true),
            gaps: is_array($gaps) ? $gaps : json_decode((string) $gaps, true),
            opportunities: is_array($opportunities) ? $opportunities : json_decode((string) $opportunities, true),
            status: GapAnalysisStatus::from($model->getAttribute('status')),
            generatedAt: new DateTimeImmutable($model->getAttribute('generated_at')->format('Y-m-d H:i:s')),
            expiresAt: new DateTimeImmutable($model->getAttribute('expires_at')->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->format('Y-m-d H:i:s')),
        );
    }
}
