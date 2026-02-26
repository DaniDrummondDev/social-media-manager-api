<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GapAnalysisOpportunitiesOutput
{
    /**
     * @param  array<array{topic: string, opportunity_score: int, competitor_count: int, recommendation: string}>  $opportunities
     */
    public function __construct(
        public array $opportunities,
        public int $totalGaps,
        public int $actionableOpportunities,
    ) {}
}
