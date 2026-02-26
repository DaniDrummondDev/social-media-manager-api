<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\PredictionBreakdown;
use App\Domain\AIIntelligence\ValueObjects\PredictionRecommendation;
use App\Domain\AIIntelligence\ValueObjects\PredictionScore;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\PerformancePredictionModel;
use DateTimeImmutable;

final class EloquentPerformancePredictionRepository implements PerformancePredictionRepositoryInterface
{
    public function __construct(
        private readonly PerformancePredictionModel $model,
    ) {}

    public function create(PerformancePrediction $prediction): void
    {
        $this->model->newQuery()->create($this->toArray($prediction));
    }

    public function findById(Uuid $id): ?PerformancePrediction
    {
        /** @var PerformancePredictionModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<PerformancePrediction>
     */
    public function findByContentId(Uuid $contentId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, PerformancePredictionModel> $records */
        $records = $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (PerformancePredictionModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array{items: array<PerformancePrediction>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, PerformancePredictionModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (PerformancePredictionModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(PerformancePrediction $prediction): array
    {
        return [
            'id' => (string) $prediction->id,
            'organization_id' => (string) $prediction->organizationId,
            'content_id' => (string) $prediction->contentId,
            'provider' => $prediction->provider,
            'overall_score' => $prediction->overallScore->value,
            'breakdown' => $prediction->breakdown->toArray(),
            'similar_content_ids' => $prediction->similarContentIds,
            'recommendations' => array_map(
                fn (PredictionRecommendation $r) => $r->toArray(),
                $prediction->recommendations,
            ),
            'model_version' => $prediction->modelVersion,
            'created_at' => $prediction->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(PerformancePredictionModel $model): PerformancePrediction
    {
        $breakdown = $model->getAttribute('breakdown');
        $recommendations = $model->getAttribute('recommendations');
        $similarContentIds = $model->getAttribute('similar_content_ids');
        $createdAt = $model->getAttribute('created_at');

        $breakdownArray = is_array($breakdown) ? $breakdown : json_decode((string) $breakdown, true);
        $recsArray = is_array($recommendations) ? $recommendations : json_decode((string) $recommendations, true);
        $similarIds = is_array($similarContentIds)
            ? $similarContentIds
            : ($similarContentIds !== null ? json_decode((string) $similarContentIds, true) : null);

        return PerformancePrediction::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            provider: $model->getAttribute('provider'),
            overallScore: PredictionScore::create((int) $model->getAttribute('overall_score')),
            breakdown: PredictionBreakdown::fromArray($breakdownArray),
            similarContentIds: $similarIds,
            recommendations: array_map(
                fn (array $data) => PredictionRecommendation::fromArray($data),
                $recsArray ?? [],
            ),
            modelVersion: $model->getAttribute('model_version'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
