<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Repositories;

use App\Domain\ContentAI\Contracts\PromptExperimentRepositoryInterface;
use App\Domain\ContentAI\Entities\PromptExperiment;
use App\Domain\ContentAI\ValueObjects\ExperimentStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ContentAI\Models\PromptExperimentModel;
use DateTimeImmutable;

final class EloquentPromptExperimentRepository implements PromptExperimentRepositoryInterface
{
    public function __construct(
        private readonly PromptExperimentModel $model,
    ) {}

    public function create(PromptExperiment $experiment): void
    {
        $this->model->newQuery()->create($this->toArray($experiment));
    }

    public function update(PromptExperiment $experiment): void
    {
        $this->model->newQuery()
            ->where('id', (string) $experiment->id)
            ->update($this->toArray($experiment));
    }

    public function findById(Uuid $id): ?PromptExperiment
    {
        /** @var PromptExperimentModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findRunning(Uuid $organizationId, string $generationType): ?PromptExperiment
    {
        /** @var PromptExperimentModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('generation_type', $generationType)
            ->where('status', 'running')
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function hasRunningExperiment(Uuid $organizationId, string $generationType): bool
    {
        return $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('generation_type', $generationType)
            ->where('status', 'running')
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(PromptExperiment $experiment): array
    {
        return [
            'id' => (string) $experiment->id,
            'organization_id' => (string) $experiment->organizationId,
            'generation_type' => $experiment->generationType,
            'name' => $experiment->name,
            'status' => $experiment->status->value,
            'variant_a_id' => (string) $experiment->variantAId,
            'variant_b_id' => (string) $experiment->variantBId,
            'traffic_split' => $experiment->trafficSplit,
            'min_sample_size' => $experiment->minSampleSize,
            'variant_a_uses' => $experiment->variantAUses,
            'variant_a_accepted' => $experiment->variantAAccepted,
            'variant_b_uses' => $experiment->variantBUses,
            'variant_b_accepted' => $experiment->variantBAccepted,
            'winner_id' => $experiment->winnerId !== null ? (string) $experiment->winnerId : null,
            'confidence_level' => $experiment->confidenceLevel,
            'started_at' => $experiment->startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $experiment->completedAt?->format('Y-m-d H:i:s'),
            'created_at' => $experiment->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $experiment->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(PromptExperimentModel $model): PromptExperiment
    {
        $winnerId = $model->getAttribute('winner_id');
        $startedAt = $model->getAttribute('started_at');
        $completedAt = $model->getAttribute('completed_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return PromptExperiment::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            generationType: $model->getAttribute('generation_type'),
            name: $model->getAttribute('name'),
            status: ExperimentStatus::from($model->getAttribute('status')),
            variantAId: Uuid::fromString($model->getAttribute('variant_a_id')),
            variantBId: Uuid::fromString($model->getAttribute('variant_b_id')),
            trafficSplit: (float) $model->getAttribute('traffic_split'),
            minSampleSize: (int) $model->getAttribute('min_sample_size'),
            variantAUses: (int) $model->getAttribute('variant_a_uses'),
            variantAAccepted: (int) $model->getAttribute('variant_a_accepted'),
            variantBUses: (int) $model->getAttribute('variant_b_uses'),
            variantBAccepted: (int) $model->getAttribute('variant_b_accepted'),
            winnerId: $winnerId !== null ? Uuid::fromString($winnerId) : null,
            confidenceLevel: $model->getAttribute('confidence_level') !== null
                ? (float) $model->getAttribute('confidence_level')
                : null,
            startedAt: $startedAt ? new DateTimeImmutable($startedAt->format('Y-m-d H:i:s')) : null,
            completedAt: $completedAt ? new DateTimeImmutable($completedAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
