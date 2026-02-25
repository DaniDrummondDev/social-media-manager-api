<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\AdminPlanOutput;

final class ListPlansAdminUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    /**
     * @return array<AdminPlanOutput>
     */
    public function execute(): array
    {
        $plans = $this->queryService->listAllPlans();

        return array_map(
            fn (array $data) => AdminPlanOutput::fromArray($data),
            $plans,
        );
    }
}
