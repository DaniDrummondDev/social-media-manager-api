<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\CrmConversionAttribution;
use App\Domain\AIIntelligence\Repositories\CrmConversionAttributionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AttributionType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\CrmConversionAttributionModel;
use DateTimeImmutable;

final class EloquentCrmConversionAttributionRepository implements CrmConversionAttributionRepositoryInterface
{
    public function __construct(
        private readonly CrmConversionAttributionModel $model,
    ) {}

    public function create(CrmConversionAttribution $attribution): void
    {
        $this->model->newQuery()->create($this->toArray($attribution));
    }

    public function findById(Uuid $id): ?CrmConversionAttribution
    {
        /** @var CrmConversionAttributionModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<CrmConversionAttribution>
     */
    public function findByContentId(Uuid $contentId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmConversionAttributionModel> $records */
        $records = $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->orderByDesc('attributed_at')
            ->get();

        return $records->map(fn (CrmConversionAttributionModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<CrmConversionAttribution>
     */
    public function findByOrganization(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->orderByDesc('attributed_at')
            ->limit($limit);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmConversionAttributionModel> $records */
        $records = $query->get();

        return $records->map(fn (CrmConversionAttributionModel $r) => $this->toDomain($r))->all();
    }

    public function countByOrganizationAndType(Uuid $organizationId, AttributionType $type): int
    {
        return $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('attribution_type', $type->value)
            ->count();
    }

    public function sumValueByOrganization(Uuid $organizationId): float
    {
        return (float) $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNotNull('attribution_value')
            ->sum('attribution_value');
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(CrmConversionAttribution $attribution): array
    {
        return [
            'id' => (string) $attribution->id,
            'organization_id' => (string) $attribution->organizationId,
            'crm_connection_id' => (string) $attribution->crmConnectionId,
            'content_id' => (string) $attribution->contentId,
            'crm_entity_type' => $attribution->crmEntityType,
            'crm_entity_id' => $attribution->crmEntityId,
            'attribution_type' => $attribution->attributionType->value,
            'attribution_value' => $attribution->attributionValue,
            'currency' => $attribution->currency,
            'crm_stage' => $attribution->crmStage,
            'interaction_data' => $attribution->interactionData,
            'attributed_at' => $attribution->attributedAt->format('Y-m-d H:i:s'),
            'created_at' => $attribution->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(CrmConversionAttributionModel $model): CrmConversionAttribution
    {
        $interactionData = $model->getAttribute('interaction_data');
        $interactionArray = is_array($interactionData) ? $interactionData : json_decode((string) $interactionData, true);

        $attributedAt = $model->getAttribute('attributed_at');
        $createdAt = $model->getAttribute('created_at');

        return CrmConversionAttribution::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            crmConnectionId: Uuid::fromString($model->getAttribute('crm_connection_id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            crmEntityType: $model->getAttribute('crm_entity_type'),
            crmEntityId: $model->getAttribute('crm_entity_id'),
            attributionType: AttributionType::from($model->getAttribute('attribution_type')),
            attributionValue: $model->getAttribute('attribution_value'),
            currency: $model->getAttribute('currency'),
            crmStage: $model->getAttribute('crm_stage'),
            interactionData: $interactionArray ?? [],
            attributedAt: new DateTimeImmutable($attributedAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
