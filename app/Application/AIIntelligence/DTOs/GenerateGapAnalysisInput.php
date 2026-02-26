<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateGapAnalysisInput
{
    /**
     * @param  array<string>  $competitorQueryIds
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public array $competitorQueryIds,
        public int $periodDays = 30,
    ) {}
}
