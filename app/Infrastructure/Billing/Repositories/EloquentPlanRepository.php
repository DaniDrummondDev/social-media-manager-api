<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Repositories;

use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Models\PlanModel;
use DateTimeImmutable;

final class EloquentPlanRepository implements PlanRepositoryInterface
{
    public function __construct(
        private readonly PlanModel $model,
    ) {}

    public function findById(Uuid $id): ?Plan
    {
        /** @var PlanModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findBySlug(string $slug): ?Plan
    {
        /** @var PlanModel|null $record */
        $record = $this->model->newQuery()->where('slug', $slug)->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<Plan>
     */
    public function findAllActive(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, PlanModel> $records */
        $records = $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $records->map(fn (PlanModel $r) => $this->toDomain($r))->all();
    }

    public function findFreePlan(): ?Plan
    {
        return $this->findBySlug('free');
    }

    private function toDomain(PlanModel $model): Plan
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return Plan::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            name: $model->getAttribute('name'),
            slug: $model->getAttribute('slug'),
            description: $model->getAttribute('description'),
            priceMonthly: Money::fromCents((int) $model->getAttribute('price_monthly_cents'), $model->getAttribute('currency') ?? 'BRL'),
            priceYearly: Money::fromCents((int) $model->getAttribute('price_yearly_cents'), $model->getAttribute('currency') ?? 'BRL'),
            limits: PlanLimits::fromArray($model->getAttribute('limits') ?? []),
            features: PlanFeatures::fromArray($model->getAttribute('features') ?? []),
            isActive: (bool) $model->getAttribute('is_active'),
            sortOrder: (int) $model->getAttribute('sort_order'),
            stripePriceMonthlyId: $model->getAttribute('stripe_price_monthly_id'),
            stripePriceYearlyId: $model->getAttribute('stripe_price_yearly_id'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
