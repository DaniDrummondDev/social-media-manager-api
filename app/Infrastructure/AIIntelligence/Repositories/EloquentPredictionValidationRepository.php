<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\PredictionValidation;
use App\Domain\AIIntelligence\Repositories\PredictionValidationRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\PredictionAccuracy;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\PredictionValidationModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentPredictionValidationRepository implements PredictionValidationRepositoryInterface
{
    public function __construct(
        private readonly PredictionValidationModel $model,
    ) {}

    public function create(PredictionValidation $validation): void
    {
        $this->model->newQuery()->create($this->toArray($validation));
    }

    public function findById(Uuid $id): ?PredictionValidation
    {
        /** @var PredictionValidationModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByPredictionId(Uuid $predictionId): ?PredictionValidation
    {
        /** @var PredictionValidationModel|null $record */
        $record = $this->model->newQuery()
            ->where('prediction_id', (string) $predictionId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<PredictionValidation>
     */
    public function findByOrganization(Uuid $organizationId, int $limit = 50): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, PredictionValidationModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->orderByDesc('validated_at')
            ->limit($limit)
            ->get();

        return $records->map(fn (PredictionValidationModel $r) => $this->toDomain($r))->all();
    }

    public function countByOrganization(Uuid $organizationId): int
    {
        return $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->count();
    }

    /**
     * @return array{mae: float, count: int}|null
     */
    public function calculateAccuracyMetrics(Uuid $organizationId): ?array
    {
        $result = DB::table('prediction_validations')
            ->where('organization_id', (string) $organizationId)
            ->whereNotNull('absolute_error')
            ->selectRaw('AVG(absolute_error) as mae, COUNT(*) as count')
            ->first();

        if ($result === null || (int) $result->count === 0) {
            return null;
        }

        return [
            'mae' => round((float) $result->mae, 2),
            'count' => (int) $result->count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(PredictionValidation $validation): array
    {
        return [
            'id' => (string) $validation->id,
            'organization_id' => (string) $validation->organizationId,
            'prediction_id' => (string) $validation->predictionId,
            'content_id' => (string) $validation->contentId,
            'provider' => $validation->provider,
            'predicted_score' => $validation->predictedScore,
            'actual_engagement_rate' => $validation->actualEngagementRate,
            'actual_normalized_score' => $validation->actualNormalizedScore,
            'absolute_error' => $validation->accuracy?->absoluteError,
            'prediction_accuracy' => $validation->accuracy?->accuracyPercentage,
            'metrics_snapshot' => $validation->metricsSnapshot,
            'validated_at' => $validation->validatedAt->format('Y-m-d H:i:s'),
            'metrics_captured_at' => $validation->metricsCapturedAt->format('Y-m-d H:i:s'),
            'created_at' => $validation->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(PredictionValidationModel $model): PredictionValidation
    {
        $absoluteError = $model->getAttribute('absolute_error');
        $accuracyPct = $model->getAttribute('prediction_accuracy');
        $validatedAt = $model->getAttribute('validated_at');
        $metricsCapturedAt = $model->getAttribute('metrics_captured_at');
        $createdAt = $model->getAttribute('created_at');

        $metricsSnapshot = $model->getAttribute('metrics_snapshot');
        $metricsArray = is_array($metricsSnapshot) ? $metricsSnapshot : json_decode((string) $metricsSnapshot, true);

        $accuracy = ($absoluteError !== null && $accuracyPct !== null)
            ? new PredictionAccuracy((int) $absoluteError, (float) $accuracyPct)
            : null;

        return PredictionValidation::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            predictionId: Uuid::fromString($model->getAttribute('prediction_id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            provider: $model->getAttribute('provider'),
            predictedScore: (int) $model->getAttribute('predicted_score'),
            actualEngagementRate: $model->getAttribute('actual_engagement_rate') !== null
                ? (float) $model->getAttribute('actual_engagement_rate')
                : null,
            actualNormalizedScore: $model->getAttribute('actual_normalized_score') !== null
                ? (int) $model->getAttribute('actual_normalized_score')
                : null,
            accuracy: $accuracy,
            metricsSnapshot: $metricsArray,
            validatedAt: new DateTimeImmutable($validatedAt->format('Y-m-d H:i:s')),
            metricsCapturedAt: new DateTimeImmutable($metricsCapturedAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
