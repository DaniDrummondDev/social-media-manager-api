<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\PostingTimeRecommendation;
use App\Domain\AIIntelligence\Repositories\PostingTimeRecommendationRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\AIIntelligence\ValueObjects\TimeSlotScore;
use App\Domain\AIIntelligence\ValueObjects\TopSlot;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\PostingTimeRecommendationModel;
use DateTimeImmutable;

final class EloquentPostingTimeRecommendationRepository implements PostingTimeRecommendationRepositoryInterface
{
    public function __construct(
        private readonly PostingTimeRecommendationModel $model,
    ) {}

    public function create(PostingTimeRecommendation $recommendation): void
    {
        $this->model->newQuery()->create($this->toArray($recommendation));
    }

    public function update(PostingTimeRecommendation $recommendation): void
    {
        $this->model->newQuery()
            ->where('id', (string) $recommendation->id)
            ->update($this->toArray($recommendation));
    }

    public function findById(Uuid $id): ?PostingTimeRecommendation
    {
        /** @var PostingTimeRecommendationModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByOrganization(
        Uuid $organizationId,
        ?string $provider = null,
        ?Uuid $socialAccountId = null,
    ): ?PostingTimeRecommendation {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($provider !== null) {
            $query->where('provider', $provider);
        }

        if ($socialAccountId !== null) {
            $query->where('social_account_id', (string) $socialAccountId);
        }

        /** @var PostingTimeRecommendationModel|null $record */
        $record = $query->orderByDesc('calculated_at')->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function deleteByOrganization(Uuid $organizationId, ?string $provider = null): void
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($provider !== null) {
            $query->where('provider', $provider);
        }

        $query->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(PostingTimeRecommendation $recommendation): array
    {
        return [
            'id' => (string) $recommendation->id,
            'organization_id' => (string) $recommendation->organizationId,
            'social_account_id' => $recommendation->socialAccountId ? (string) $recommendation->socialAccountId : null,
            'provider' => $recommendation->provider,
            'heatmap' => array_map(fn (TimeSlotScore $slot) => $slot->toArray(), $recommendation->heatmap),
            'top_slots' => array_map(fn (TopSlot $slot) => $slot->toArray(), $recommendation->topSlots),
            'worst_slots' => array_map(fn (TopSlot $slot) => $slot->toArray(), $recommendation->worstSlots),
            'sample_size' => $recommendation->sampleSize,
            'confidence_level' => $recommendation->confidenceLevel->value,
            'calculated_at' => $recommendation->calculatedAt->format('Y-m-d H:i:s'),
            'expires_at' => $recommendation->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $recommendation->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(PostingTimeRecommendationModel $model): PostingTimeRecommendation
    {
        $heatmap = $model->getAttribute('heatmap');
        $topSlots = $model->getAttribute('top_slots');
        $worstSlots = $model->getAttribute('worst_slots');
        $socialAccountId = $model->getAttribute('social_account_id');
        $calculatedAt = $model->getAttribute('calculated_at');
        $expiresAt = $model->getAttribute('expires_at');
        $createdAt = $model->getAttribute('created_at');

        $heatmapArray = is_array($heatmap) ? $heatmap : json_decode((string) $heatmap, true);
        $topSlotsArray = is_array($topSlots) ? $topSlots : json_decode((string) $topSlots, true);
        $worstSlotsArray = is_array($worstSlots) ? $worstSlots : json_decode((string) $worstSlots, true);

        return PostingTimeRecommendation::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            socialAccountId: $socialAccountId ? Uuid::fromString($socialAccountId) : null,
            provider: $model->getAttribute('provider'),
            heatmap: array_map(fn (array $data) => TimeSlotScore::fromArray($data), $heatmapArray),
            topSlots: array_map(fn (array $data) => TopSlot::fromArray($data), $topSlotsArray),
            worstSlots: array_map(fn (array $data) => TopSlot::fromArray($data), $worstSlotsArray),
            sampleSize: (int) $model->getAttribute('sample_size'),
            confidenceLevel: ConfidenceLevel::from($model->getAttribute('confidence_level')),
            calculatedAt: new DateTimeImmutable($calculatedAt->format('Y-m-d H:i:s')),
            expiresAt: new DateTimeImmutable($expiresAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
