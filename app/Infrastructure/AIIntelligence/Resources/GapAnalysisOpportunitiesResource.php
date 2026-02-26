<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\GapAnalysisOpportunitiesOutput;

final readonly class GapAnalysisOpportunitiesResource
{
    private function __construct(
        private GapAnalysisOpportunitiesOutput $output,
    ) {}

    public static function fromOutput(GapAnalysisOpportunitiesOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'opportunities' => $this->output->opportunities,
            'total_gaps' => $this->output->totalGaps,
            'actionable_opportunities' => $this->output->actionableOpportunities,
        ];
    }
}
