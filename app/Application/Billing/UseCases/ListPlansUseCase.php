<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\PlanOutput;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;

final class ListPlansUseCase
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
    ) {}

    /**
     * @return array<PlanOutput>
     */
    public function execute(): array
    {
        $plans = $this->planRepository->findAllActive();

        return array_map(
            fn ($plan) => PlanOutput::fromEntity($plan),
            $plans,
        );
    }
}
