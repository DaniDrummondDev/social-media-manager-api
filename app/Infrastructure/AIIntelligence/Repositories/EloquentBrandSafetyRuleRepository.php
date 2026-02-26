<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\BrandSafetyRule;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyRuleType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\BrandSafetyRuleModel;
use DateTimeImmutable;

final class EloquentBrandSafetyRuleRepository implements BrandSafetyRuleRepositoryInterface
{
    public function __construct(
        private readonly BrandSafetyRuleModel $model,
    ) {}

    public function create(BrandSafetyRule $rule): void
    {
        $this->model->newQuery()->create($this->toArray($rule));
    }

    public function update(BrandSafetyRule $rule): void
    {
        $this->model->newQuery()
            ->where('id', (string) $rule->id)
            ->update($this->toArray($rule));
    }

    public function findById(Uuid $id): ?BrandSafetyRule
    {
        /** @var BrandSafetyRuleModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<BrandSafetyRule>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, BrandSafetyRuleModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (BrandSafetyRuleModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<BrandSafetyRule>
     */
    public function findActiveByOrganizationId(Uuid $organizationId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, BrandSafetyRuleModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('is_active', true)
            ->get();

        return $records->map(fn (BrandSafetyRuleModel $r) => $this->toDomain($r))->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(BrandSafetyRule $rule): array
    {
        return [
            'id' => (string) $rule->id,
            'organization_id' => (string) $rule->organizationId,
            'rule_type' => $rule->ruleType->value,
            'rule_config' => $rule->ruleConfig,
            'severity' => $rule->severity->value,
            'is_active' => $rule->isActive,
            'created_at' => $rule->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $rule->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(BrandSafetyRuleModel $model): BrandSafetyRule
    {
        $ruleConfig = $model->getAttribute('rule_config');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        $ruleConfigArray = is_array($ruleConfig) ? $ruleConfig : json_decode((string) $ruleConfig, true);

        return BrandSafetyRule::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            ruleType: SafetyRuleType::from($model->getAttribute('rule_type')),
            ruleConfig: $ruleConfigArray,
            severity: RuleSeverity::from($model->getAttribute('severity')),
            isActive: (bool) $model->getAttribute('is_active'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
