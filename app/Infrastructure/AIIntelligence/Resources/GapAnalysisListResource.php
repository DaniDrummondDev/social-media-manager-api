<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\GapAnalysisListOutput;

final readonly class GapAnalysisListResource
{
    private function __construct(
        private GapAnalysisListOutput $output,
    ) {}

    public static function fromOutput(GapAnalysisListOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'gap_analysis',
            'attributes' => [
                'status' => $this->output->status,
                'competitor_query_count' => $this->output->competitorQueryCount,
                'analysis_period_start' => $this->output->analysisPeriodStart,
                'analysis_period_end' => $this->output->analysisPeriodEnd,
                'gap_count' => $this->output->gapCount,
                'opportunity_count' => $this->output->opportunityCount,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
