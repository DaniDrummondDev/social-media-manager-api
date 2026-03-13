<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\AutomationRuleConditionModel;
use App\Infrastructure\Engagement\Models\AutomationRuleModel;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class EloquentAutomationRuleRepository implements AutomationRuleRepositoryInterface
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly AutomationRuleModel $model,
        private readonly CacheRepository $cache,
    ) {}

    public function create(AutomationRule $rule): void
    {
        $this->model->newQuery()->create($this->toArray($rule));

        $this->syncConditions($rule);
        $this->invalidateCache($rule->organizationId);
    }

    public function update(AutomationRule $rule): void
    {
        $this->model->newQuery()
            ->where('id', (string) $rule->id)
            ->update($this->toArray($rule));

        $this->syncConditions($rule);
        $this->invalidateCache($rule->organizationId);
    }

    public function findById(Uuid $id): ?AutomationRule
    {
        /** @var AutomationRuleModel|null $record */
        $record = $this->model->newQuery()->with('conditions')->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<AutomationRule>
     */
    public function findActiveByOrganizationId(Uuid $organizationId, int $limit = 100): array
    {
        $cacheKey = $this->getActiveCacheKey($organizationId);

        /** @var array<AutomationRule>|null $cached */
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return array_slice($cached, 0, $limit);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, AutomationRuleModel> $records */
        $records = $this->model->newQuery()
            ->with('conditions')
            ->where('organization_id', (string) $organizationId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('priority')
            ->limit($limit)
            ->get();

        $rules = $records->map(fn (AutomationRuleModel $r) => $this->toDomain($r))->all();

        $this->cache->put($cacheKey, $rules, self::CACHE_TTL_SECONDS);

        return $rules;
    }

    /**
     * @return array<AutomationRule>
     */
    public function findByOrganizationId(Uuid $organizationId, int $limit = 100): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, AutomationRuleModel> $records */
        $records = $this->model->newQuery()
            ->with('conditions')
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->orderBy('priority')
            ->limit($limit)
            ->get();

        return $records->map(fn (AutomationRuleModel $r) => $this->toDomain($r))->all();
    }

    public function delete(Uuid $id): void
    {
        // Get the rule first to invalidate cache for its organization
        /** @var AutomationRuleModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        $this->model->newQuery()->where('id', (string) $id)->delete();

        if ($record !== null) {
            $this->invalidateCache(Uuid::fromString($record->getAttribute('organization_id')));
        }
    }

    public function isPriorityTaken(Uuid $organizationId, int $priority, ?Uuid $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('priority', $priority)
            ->whereNull('deleted_at');

        if ($excludeId !== null) {
            $query->where('id', '!=', (string) $excludeId);
        }

        return $query->exists();
    }

    private function syncConditions(AutomationRule $rule): void
    {
        AutomationRuleConditionModel::query()
            ->where('automation_rule_id', (string) $rule->id)
            ->delete();

        foreach ($rule->conditions as $condition) {
            AutomationRuleConditionModel::query()->create([
                'id' => (string) Uuid::generate(),
                'automation_rule_id' => (string) $rule->id,
                'field' => $condition->field,
                'operator' => $condition->operator->value,
                'value' => $condition->value,
                'is_case_sensitive' => $condition->isCaseSensitive,
                'position' => $condition->position,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AutomationRule $rule): array
    {
        return [
            'id' => (string) $rule->id,
            'organization_id' => (string) $rule->organizationId,
            'name' => $rule->name,
            'priority' => $rule->priority,
            'action_type' => $rule->actionType->value,
            'response_template' => $rule->responseTemplate,
            'webhook_id' => $rule->webhookId !== null ? (string) $rule->webhookId : null,
            'delay_seconds' => $rule->delaySeconds,
            'daily_limit' => $rule->dailyLimit,
            'is_active' => $rule->isActive,
            'applies_to_networks' => $rule->appliesToNetworks,
            'applies_to_campaigns' => $rule->appliesToCampaigns,
            'deleted_at' => $rule->deletedAt?->format('Y-m-d H:i:s'),
            'purge_at' => $rule->purgeAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(AutomationRuleModel $model): AutomationRule
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, AutomationRuleConditionModel> $conditionsRelation */
        $conditionsRelation = $model->getAttribute('conditions');

        $conditions = $conditionsRelation->map(fn (AutomationRuleConditionModel $c) => new RuleCondition(
            field: $c->getAttribute('field'),
            operator: ConditionOperator::from($c->getAttribute('operator')),
            value: $c->getAttribute('value'),
            isCaseSensitive: (bool) $c->getAttribute('is_case_sensitive'),
            position: (int) $c->getAttribute('position'),
        ))->all();

        $webhookId = $model->getAttribute('webhook_id');
        $deletedAt = $model->getAttribute('deleted_at');
        $purgeAt = $model->getAttribute('purge_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return AutomationRule::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            name: $model->getAttribute('name'),
            priority: (int) $model->getAttribute('priority'),
            conditions: $conditions,
            actionType: ActionType::from($model->getAttribute('action_type')),
            responseTemplate: $model->getAttribute('response_template'),
            webhookId: $webhookId !== null ? Uuid::fromString($webhookId) : null,
            delaySeconds: (int) $model->getAttribute('delay_seconds'),
            dailyLimit: (int) $model->getAttribute('daily_limit'),
            isActive: (bool) $model->getAttribute('is_active'),
            appliesToNetworks: $model->getAttribute('applies_to_networks'),
            appliesToCampaigns: $model->getAttribute('applies_to_campaigns'),
            deletedAt: $deletedAt ? new DateTimeImmutable($deletedAt->format('Y-m-d H:i:s')) : null,
            purgeAt: $purgeAt ? new DateTimeImmutable($purgeAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }

    private function getActiveCacheKey(Uuid $organizationId): string
    {
        return "automation_rules:active:org:{$organizationId}";
    }

    private function invalidateCache(Uuid $organizationId): void
    {
        $this->cache->forget($this->getActiveCacheKey($organizationId));
    }
}
