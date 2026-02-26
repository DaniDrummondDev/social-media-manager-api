<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\GapAnalysisOutput;

final readonly class GapAnalysisResource
{
    private function __construct(
        private GapAnalysisOutput $output,
    ) {}

    public static function fromOutput(GapAnalysisOutput $output): self
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
                'competitor_query_ids' => $this->output->competitorQueryIds,
                'analysis_period' => [
                    'start' => $this->output->analysisPeriodStart,
                    'end' => $this->output->analysisPeriodEnd,
                ],
                'our_topics' => $this->output->ourTopics,
                'competitor_topics' => $this->output->competitorTopics,
                'gaps' => $this->output->gaps,
                'opportunities' => $this->output->opportunities,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
