<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\AudienceInsight;
use App\Domain\AIIntelligence\Repositories\AudienceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\AudienceInsightModel;
use DateTimeImmutable;

final class EloquentAudienceInsightRepository implements AudienceInsightRepositoryInterface
{
    public function __construct(
        private readonly AudienceInsightModel $model,
    ) {}

    public function create(AudienceInsight $insight): void
    {
        $this->model->newQuery()->create($this->toArray($insight));
    }

    public function findById(Uuid $id): ?AudienceInsight
    {
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByOrganizationAndType(
        Uuid $organizationId,
        InsightType $type,
        ?Uuid $socialAccountId = null,
    ): ?AudienceInsight {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('insight_type', $type->value)
            ->where('expires_at', '>', now());

        if ($socialAccountId !== null) {
            $query->where('social_account_id', (string) $socialAccountId);
        }

        $record = $query->orderByDesc('generated_at')->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<AudienceInsight>
     */
    public function findActiveByOrganization(Uuid $organizationId): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('expires_at', '>', now())
            ->orderByDesc('generated_at')
            ->get();

        return $records->map(fn ($record) => $this->toDomain($record))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AudienceInsight $insight): array
    {
        return [
            'id' => (string) $insight->id,
            'organization_id' => (string) $insight->organizationId,
            'social_account_id' => $insight->socialAccountId ? (string) $insight->socialAccountId : null,
            'insight_type' => $insight->insightType->value,
            'insight_data' => $insight->insightData,
            'source_comment_count' => $insight->sourceCommentCount,
            'period_start' => $insight->periodStart->format('Y-m-d H:i:s'),
            'period_end' => $insight->periodEnd->format('Y-m-d H:i:s'),
            'confidence_score' => $insight->confidenceScore,
            'generated_at' => $insight->generatedAt->format('Y-m-d H:i:s'),
            'expires_at' => $insight->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $insight->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(AudienceInsightModel $model): AudienceInsight
    {
        $insightData = $model->getAttribute('insight_data');

        return AudienceInsight::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            socialAccountId: $model->getAttribute('social_account_id')
                ? Uuid::fromString($model->getAttribute('social_account_id'))
                : null,
            insightType: InsightType::from($model->getAttribute('insight_type')),
            insightData: is_array($insightData) ? $insightData : json_decode((string) $insightData, true),
            sourceCommentCount: (int) $model->getAttribute('source_comment_count'),
            periodStart: new DateTimeImmutable($model->getAttribute('period_start')->format('Y-m-d H:i:s')),
            periodEnd: new DateTimeImmutable($model->getAttribute('period_end')->format('Y-m-d H:i:s')),
            confidenceScore: $model->getAttribute('confidence_score') !== null
                ? (float) $model->getAttribute('confidence_score')
                : null,
            generatedAt: new DateTimeImmutable($model->getAttribute('generated_at')->format('Y-m-d H:i:s')),
            expiresAt: new DateTimeImmutable($model->getAttribute('expires_at')->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->format('Y-m-d H:i:s')),
        );
    }
}
