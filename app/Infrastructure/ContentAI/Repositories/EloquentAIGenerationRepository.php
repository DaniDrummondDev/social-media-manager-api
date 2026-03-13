<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Repositories;

use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ContentAI\Models\AIGenerationModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentAIGenerationRepository implements AIGenerationRepositoryInterface
{
    public function __construct(
        private readonly AIGenerationModel $model,
    ) {}

    public function create(AIGeneration $generation): void
    {
        $this->model->newQuery()->create($this->toArray($generation));
    }

    public function findById(Uuid $id): ?AIGeneration
    {
        /** @var AIGenerationModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return AIGeneration[]
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $type = null, int $limit = 100): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($type !== null) {
            $query->where('type', $type);
        }

        $records = $query->orderByDesc('created_at')->limit($limit)->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, AIGenerationModel> $records */
        return $records->map(fn (AIGenerationModel $record) => $this->toDomain($record))->all();
    }

    public function countByOrganizationAndMonth(Uuid $organizationId, int $year, int $month): int
    {
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $endDate = (new DateTimeImmutable($startDate))->modify('+1 month')->format('Y-m-d H:i:s');

        return (int) DB::table('ai_generations')
            ->where('organization_id', (string) $organizationId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->count();
    }

    /**
     * @return array{tokens_input: int, tokens_output: int, cost_estimate: float}
     */
    public function sumUsageByOrganizationAndMonth(Uuid $organizationId, int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $endDate = (new DateTimeImmutable($startDate))->modify('+1 month')->format('Y-m-d H:i:s');

        $result = DB::table('ai_generations')
            ->selectRaw('COALESCE(SUM(tokens_input), 0) as tokens_input')
            ->selectRaw('COALESCE(SUM(tokens_output), 0) as tokens_output')
            ->selectRaw('COALESCE(SUM(cost_estimate), 0) as cost_estimate')
            ->where('organization_id', (string) $organizationId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->first();

        return [
            'tokens_input' => (int) $result->tokens_input,
            'tokens_output' => (int) $result->tokens_output,
            'cost_estimate' => (float) $result->cost_estimate,
        ];
    }

    private function toDomain(AIGenerationModel $model): AIGeneration
    {
        return AIGeneration::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            userId: Uuid::fromString($model->getAttribute('user_id')),
            type: GenerationType::from($model->getAttribute('type')),
            input: $model->getAttribute('input') ?? [],
            output: $model->getAttribute('output') ?? [],
            usage: new AIUsage(
                tokensInput: (int) $model->getAttribute('tokens_input'),
                tokensOutput: (int) $model->getAttribute('tokens_output'),
                model: $model->getAttribute('model_used'),
                costEstimate: (float) $model->getAttribute('cost_estimate'),
                durationMs: (int) $model->getAttribute('duration_ms'),
            ),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AIGeneration $generation): array
    {
        return [
            'id' => (string) $generation->id,
            'organization_id' => (string) $generation->organizationId,
            'user_id' => (string) $generation->userId,
            'type' => $generation->type->value,
            'input' => $generation->input,
            'output' => $generation->output,
            'model_used' => $generation->usage->model,
            'tokens_input' => $generation->usage->tokensInput,
            'tokens_output' => $generation->usage->tokensOutput,
            'cost_estimate' => $generation->usage->costEstimate,
            'duration_ms' => $generation->usage->durationMs,
            'created_at' => $generation->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
