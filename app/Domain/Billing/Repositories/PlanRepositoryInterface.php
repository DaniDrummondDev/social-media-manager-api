<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Entities\Plan;
use App\Domain\Shared\ValueObjects\Uuid;

interface PlanRepositoryInterface
{
    public function findById(Uuid $id): ?Plan;

    public function findBySlug(string $slug): ?Plan;

    /**
     * @return array<Plan>
     */
    public function findAllActive(): array;

    public function findFreePlan(): ?Plan;
}
