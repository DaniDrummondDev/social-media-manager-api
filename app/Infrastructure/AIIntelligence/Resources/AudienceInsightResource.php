<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\AudienceInsightOutput;

final readonly class AudienceInsightResource
{
    private function __construct(
        private AudienceInsightOutput $output,
    ) {}

    public static function fromOutput(AudienceInsightOutput $output): self
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
            'type' => 'audience_insight',
            'attributes' => [
                'insight_type' => $this->output->insightType,
                'insight_data' => $this->output->insightData,
                'source_comment_count' => $this->output->sourceCommentCount,
                'confidence_score' => $this->output->confidenceScore,
                'period_start' => $this->output->periodStart,
                'period_end' => $this->output->periodEnd,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
