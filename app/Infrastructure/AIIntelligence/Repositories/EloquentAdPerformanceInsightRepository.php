<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\AdPerformanceInsightModel;
use DateTimeImmutable;

final class EloquentAdPerformanceInsightRepository implements AdPerformanceInsightRepositoryInterface
{
    public function __construct(
        private readonly AdPerformanceInsightModel $model,
    ) {}

    public function save(AdPerformanceInsight $insight): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['id' => (string) $insight->id],
            $this->toArray($insight),
        );
    }

    public function findById(Uuid $id): ?AdPerformanceInsight
    {
        /** @var AdPerformanceInsightModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByOrganizationAndType(
        Uuid $organizationId,
        AdInsightType $type,
    ): ?AdPerformanceInsight {
        /** @var AdPerformanceInsightModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('ad_insight_type', $type->value)
            ->orderByDesc('generated_at')
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<AdPerformanceInsight>
     */
    public function findActiveByOrganization(Uuid $organizationId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, AdPerformanceInsightModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('expires_at', '>', now())
            ->get();

        return $records->map(fn (AdPerformanceInsightModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<AdPerformanceInsight>
     */
    public function findExpired(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, AdPerformanceInsightModel> $records */
        $records = $this->model->newQuery()
            ->where('expires_at', '<=', now())
            ->get();

        return $records->map(fn (AdPerformanceInsightModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AdPerformanceInsight $insight): array
    {
        return [
            'id' => (string) $insight->id,
            'organization_id' => (string) $insight->organizationId,
            'ad_insight_type' => $insight->adInsightType->value,
            'insight_data' => $insight->insightData,
            'sample_size' => $insight->sampleSize,
            'confidence_level' => $insight->confidenceLevel->value,
            'period_start' => $insight->periodStart->format('Y-m-d H:i:s'),
            'period_end' => $insight->periodEnd->format('Y-m-d H:i:s'),
            'generated_at' => $insight->generatedAt->format('Y-m-d H:i:s'),
            'expires_at' => $insight->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $insight->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $insight->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(AdPerformanceInsightModel $model): AdPerformanceInsight
    {
        $periodStart = $model->getAttribute('period_start');
        $periodEnd = $model->getAttribute('period_end');
        $generatedAt = $model->getAttribute('generated_at');
        $expiresAt = $model->getAttribute('expires_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        $insightData = $model->getAttribute('insight_data');

        return AdPerformanceInsight::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            adInsightType: AdInsightType::from($model->getAttribute('ad_insight_type')),
            insightData: is_array($insightData) ? $insightData : json_decode((string) $insightData, true),
            sampleSize: (int) $model->getAttribute('sample_size'),
            confidenceLevel: ConfidenceLevel::from($model->getAttribute('confidence_level')),
            periodStart: new DateTimeImmutable($periodStart->format('Y-m-d H:i:s')),
            periodEnd: new DateTimeImmutable($periodEnd->format('Y-m-d H:i:s')),
            generatedAt: new DateTimeImmutable($generatedAt->format('Y-m-d H:i:s')),
            expiresAt: new DateTimeImmutable($expiresAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
