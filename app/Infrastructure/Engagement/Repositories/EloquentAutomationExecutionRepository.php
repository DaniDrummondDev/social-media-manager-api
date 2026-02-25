<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\AutomationExecution;
use App\Domain\Engagement\Repositories\AutomationExecutionRepositoryInterface;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\AutomationExecutionModel;
use DateTimeImmutable;

final class EloquentAutomationExecutionRepository implements AutomationExecutionRepositoryInterface
{
    public function __construct(
        private readonly AutomationExecutionModel $model,
    ) {}

    public function create(AutomationExecution $execution): void
    {
        $this->model->newQuery()->create([
            'id' => (string) $execution->id,
            'organization_id' => (string) $execution->organizationId,
            'automation_rule_id' => (string) $execution->automationRuleId,
            'comment_id' => (string) $execution->commentId,
            'action_type' => $execution->actionType->value,
            'response_text' => $execution->responseText,
            'success' => $execution->success,
            'error_message' => $execution->errorMessage,
            'delay_applied' => $execution->delayApplied,
            'executed_at' => $execution->executedAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<AutomationExecution>
     */
    public function findByRuleId(Uuid $ruleId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('automation_rule_id', (string) $ruleId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, AutomationExecutionModel> $records */
        $records = $query->orderByDesc('id')->limit($limit)->get();

        return $records->map(fn (AutomationExecutionModel $r) => $this->toDomain($r))->all();
    }

    public function countTodayByRule(Uuid $ruleId): int
    {
        return (int) $this->model->newQuery()
            ->where('automation_rule_id', (string) $ruleId)
            ->where('executed_at', '>=', (new DateTimeImmutable('today midnight UTC'))->format('Y-m-d H:i:s'))
            ->count();
    }

    private function toDomain(AutomationExecutionModel $model): AutomationExecution
    {
        $executedAt = $model->getAttribute('executed_at');

        return AutomationExecution::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            automationRuleId: Uuid::fromString($model->getAttribute('automation_rule_id')),
            commentId: Uuid::fromString($model->getAttribute('comment_id')),
            actionType: ActionType::from($model->getAttribute('action_type')),
            responseText: $model->getAttribute('response_text'),
            success: (bool) $model->getAttribute('success'),
            errorMessage: $model->getAttribute('error_message'),
            delayApplied: (int) $model->getAttribute('delay_applied'),
            executedAt: new DateTimeImmutable($executedAt->format('Y-m-d H:i:s')),
        );
    }
}
