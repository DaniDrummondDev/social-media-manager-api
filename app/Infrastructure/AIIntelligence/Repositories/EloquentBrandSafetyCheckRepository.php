<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\BrandSafetyCheck;
use App\Domain\AIIntelligence\Repositories\BrandSafetyCheckRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\SafetyCheckResult;
use App\Domain\AIIntelligence\ValueObjects\SafetyStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\BrandSafetyCheckModel;
use DateTimeImmutable;

final class EloquentBrandSafetyCheckRepository implements BrandSafetyCheckRepositoryInterface
{
    public function __construct(
        private readonly BrandSafetyCheckModel $model,
    ) {}

    public function create(BrandSafetyCheck $check): void
    {
        $this->model->newQuery()->create($this->toArray($check));
    }

    public function update(BrandSafetyCheck $check): void
    {
        $this->model->newQuery()
            ->where('id', (string) $check->id)
            ->update($this->toArray($check));
    }

    public function findById(Uuid $id): ?BrandSafetyCheck
    {
        /** @var BrandSafetyCheckModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<BrandSafetyCheck>
     */
    public function findByContentId(Uuid $contentId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, BrandSafetyCheckModel> $records */
        $records = $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (BrandSafetyCheckModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array{items: array<BrandSafetyCheck>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, BrandSafetyCheckModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (BrandSafetyCheckModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(BrandSafetyCheck $check): array
    {
        return [
            'id' => (string) $check->id,
            'organization_id' => (string) $check->organizationId,
            'content_id' => (string) $check->contentId,
            'provider' => $check->provider,
            'overall_status' => $check->overallStatus->value,
            'overall_score' => $check->overallScore,
            'checks' => array_map(fn (SafetyCheckResult $r) => $r->toArray(), $check->checks),
            'model_used' => $check->modelUsed,
            'tokens_input' => $check->tokensInput,
            'tokens_output' => $check->tokensOutput,
            'checked_at' => $check->checkedAt?->format('Y-m-d H:i:s'),
            'created_at' => $check->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(BrandSafetyCheckModel $model): BrandSafetyCheck
    {
        $checks = $model->getAttribute('checks');
        $checkedAt = $model->getAttribute('checked_at');
        $createdAt = $model->getAttribute('created_at');

        $checksArray = is_array($checks) ? $checks : json_decode((string) $checks, true);

        return BrandSafetyCheck::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            provider: $model->getAttribute('provider'),
            overallStatus: SafetyStatus::from($model->getAttribute('overall_status')),
            overallScore: $model->getAttribute('overall_score'),
            checks: array_map(fn (array $data) => SafetyCheckResult::fromArray($data), $checksArray),
            modelUsed: $model->getAttribute('model_used'),
            tokensInput: $model->getAttribute('tokens_input'),
            tokensOutput: $model->getAttribute('tokens_output'),
            checkedAt: $checkedAt ? new DateTimeImmutable($checkedAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
