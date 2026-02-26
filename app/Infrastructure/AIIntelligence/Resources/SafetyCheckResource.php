<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\SafetyCheckOutput;

final readonly class SafetyCheckResource
{
    public function __construct(
        private SafetyCheckOutput $output,
    ) {}

    public static function fromOutput(SafetyCheckOutput $output): self
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
            'type' => 'safety_check',
            'attributes' => [
                'content_id' => $this->output->contentId,
                'provider' => $this->output->provider,
                'overall_status' => $this->output->overallStatus,
                'overall_score' => $this->output->overallScore,
                'checks' => $this->output->checks,
                'checked_at' => $this->output->checkedAt,
            ],
        ];
    }
}
