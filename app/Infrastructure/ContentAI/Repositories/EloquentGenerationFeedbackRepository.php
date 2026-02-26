<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Repositories;

use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\ContentAI\Entities\GenerationFeedback;
use App\Domain\ContentAI\ValueObjects\DiffSummary;
use App\Domain\ContentAI\ValueObjects\FeedbackAction;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ContentAI\Models\GenerationFeedbackModel;
use DateTimeImmutable;

final class EloquentGenerationFeedbackRepository implements GenerationFeedbackRepositoryInterface
{
    public function __construct(
        private readonly GenerationFeedbackModel $model,
    ) {}

    public function create(GenerationFeedback $feedback): void
    {
        $this->model->newQuery()->create($this->toArray($feedback));
    }

    public function update(GenerationFeedback $feedback): void
    {
        $this->model->newQuery()
            ->where('id', (string) $feedback->id)
            ->update($this->toArray($feedback));
    }

    public function findById(Uuid $id): ?GenerationFeedback
    {
        /** @var GenerationFeedbackModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByGenerationId(Uuid $generationId): ?GenerationFeedback
    {
        /** @var GenerationFeedbackModel|null $record */
        $record = $this->model->newQuery()
            ->where('ai_generation_id', (string) $generationId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<GenerationFeedback>
     */
    public function findEditedByOrganizationAndType(
        Uuid $organizationId,
        string $generationType,
        int $limit = 100,
    ): array {
        /** @var \Illuminate\Database\Eloquent\Collection<int, GenerationFeedbackModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('generation_type', $generationType)
            ->where('action', 'edited')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $records->map(fn (GenerationFeedbackModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array{accepted: int, edited: int, rejected: int, total: int}
     */
    public function countByOrganizationAndType(
        Uuid $organizationId,
        string $generationType,
    ): array {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('generation_type', $generationType);

        $total = $query->count();
        $accepted = (clone $query)->where('action', 'accepted')->count();
        $edited = (clone $query)->where('action', 'edited')->count();
        $rejected = (clone $query)->where('action', 'rejected')->count();

        return [
            'accepted' => $accepted,
            'edited' => $edited,
            'rejected' => $rejected,
            'total' => $total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(GenerationFeedback $feedback): array
    {
        return [
            'id' => (string) $feedback->id,
            'organization_id' => (string) $feedback->organizationId,
            'user_id' => (string) $feedback->userId,
            'ai_generation_id' => (string) $feedback->generationId,
            'action' => $feedback->action->value,
            'original_output' => $feedback->originalOutput,
            'edited_output' => $feedback->editedOutput,
            'diff_summary' => $feedback->diffSummary?->toArray(),
            'content_id' => $feedback->contentId !== null ? (string) $feedback->contentId : null,
            'generation_type' => $feedback->generationType,
            'time_to_decision_ms' => $feedback->timeToDecisionMs,
            'created_at' => $feedback->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(GenerationFeedbackModel $model): GenerationFeedback
    {
        $diffSummary = $model->getAttribute('diff_summary');
        $diffSummaryArray = $diffSummary !== null
            ? (is_array($diffSummary) ? $diffSummary : json_decode((string) $diffSummary, true))
            : null;

        $contentId = $model->getAttribute('content_id');
        $createdAt = $model->getAttribute('created_at');

        return GenerationFeedback::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            userId: Uuid::fromString($model->getAttribute('user_id')),
            generationId: Uuid::fromString($model->getAttribute('ai_generation_id')),
            action: FeedbackAction::from($model->getAttribute('action')),
            originalOutput: is_array($model->getAttribute('original_output'))
                ? $model->getAttribute('original_output')
                : json_decode((string) $model->getAttribute('original_output'), true),
            editedOutput: $model->getAttribute('edited_output') !== null
                ? (is_array($model->getAttribute('edited_output'))
                    ? $model->getAttribute('edited_output')
                    : json_decode((string) $model->getAttribute('edited_output'), true))
                : null,
            diffSummary: $diffSummaryArray !== null ? DiffSummary::fromArray($diffSummaryArray) : null,
            contentId: $contentId !== null ? Uuid::fromString($contentId) : null,
            generationType: $model->getAttribute('generation_type'),
            timeToDecisionMs: $model->getAttribute('time_to_decision_ms'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
