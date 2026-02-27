<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PaidAdvertising\Models\AdBoostModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

final class EloquentAdBoostRepository implements AdBoostRepositoryInterface
{
    public function __construct(
        private readonly AdBoostModel $model,
    ) {}

    public function create(AdBoost $boost): void
    {
        $this->model->newQuery()->create($this->toArray($boost));
    }

    public function update(AdBoost $boost): void
    {
        $this->model->newQuery()
            ->where('id', (string) $boost->id)
            ->update($this->toArray($boost));
    }

    public function findById(Uuid $id): ?AdBoost
    {
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<AdBoost>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $cursorRecord = $this->model->newQuery()->find($cursor);

            if ($cursorRecord !== null) {
                $query->where('created_at', '<', $cursorRecord->getAttribute('created_at'));
            }
        }

        $records = $query->orderByDesc('created_at')->limit($limit + 1)->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        return [
            'items' => $items->map(fn (Model $r) => $this->toDomain($r))->values()->all(),
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<AdBoost>
     */
    public function findByScheduledPostId(Uuid $scheduledPostId): array
    {
        $records = $this->model->newQuery()
            ->where('scheduled_post_id', (string) $scheduledPostId)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<AdBoost>
     */
    public function findByStatus(AdStatus $status): array
    {
        $records = $this->model->newQuery()
            ->where('status', $status->value)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<AdBoost>
     */
    public function findActiveByAdAccountId(Uuid $adAccountId): array
    {
        $records = $this->model->newQuery()
            ->where('ad_account_id', (string) $adAccountId)
            ->where('status', AdStatus::Active->value)
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('id', (string) $id)->delete();
    }

    private function toDomain(Model $model): AdBoost
    {
        $startedAt = $model->getAttribute('started_at');
        $completedAt = $model->getAttribute('completed_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return AdBoost::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            scheduledPostId: Uuid::fromString($model->getAttribute('scheduled_post_id')),
            adAccountId: Uuid::fromString($model->getAttribute('ad_account_id')),
            audienceId: Uuid::fromString($model->getAttribute('audience_id')),
            budget: AdBudget::create(
                (int) $model->getAttribute('budget_amount_cents'),
                $model->getAttribute('budget_currency'),
                BudgetType::from($model->getAttribute('budget_type')),
            ),
            durationDays: (int) $model->getAttribute('duration_days'),
            objective: AdObjective::from($model->getAttribute('objective')),
            status: AdStatus::from($model->getAttribute('status')),
            externalIds: $model->getAttribute('external_ids'),
            rejectionReason: $model->getAttribute('rejection_reason'),
            startedAt: $startedAt !== null ? new DateTimeImmutable($startedAt->format('Y-m-d H:i:s')) : null,
            completedAt: $completedAt !== null ? new DateTimeImmutable($completedAt->format('Y-m-d H:i:s')) : null,
            createdBy: Uuid::fromString($model->getAttribute('created_by')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AdBoost $boost): array
    {
        return [
            'id' => (string) $boost->id,
            'organization_id' => (string) $boost->organizationId,
            'scheduled_post_id' => (string) $boost->scheduledPostId,
            'ad_account_id' => (string) $boost->adAccountId,
            'audience_id' => (string) $boost->audienceId,
            'budget_amount_cents' => $boost->budget->amountCents,
            'budget_currency' => $boost->budget->currency,
            'budget_type' => $boost->budget->type->value,
            'duration_days' => $boost->durationDays,
            'objective' => $boost->objective->value,
            'status' => $boost->status->value,
            'external_ids' => $boost->externalIds,
            'rejection_reason' => $boost->rejectionReason,
            'started_at' => $boost->startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $boost->completedAt?->format('Y-m-d H:i:s'),
            'created_by' => (string) $boost->createdBy,
        ];
    }
}
