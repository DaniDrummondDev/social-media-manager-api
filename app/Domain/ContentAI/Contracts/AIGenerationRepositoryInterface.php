<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Contracts;

use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\Shared\ValueObjects\Uuid;

interface AIGenerationRepositoryInterface
{
    public function create(AIGeneration $generation): void;

    public function findById(Uuid $id): ?AIGeneration;

    /**
     * @return AIGeneration[]
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $type = null, int $limit = 100): array;

    public function countByOrganizationAndMonth(Uuid $organizationId, int $year, int $month): int;

    /**
     * @return array{tokens_input: int, tokens_output: int, cost_estimate: float}
     */
    public function sumUsageByOrganizationAndMonth(Uuid $organizationId, int $year, int $month): array;
}
