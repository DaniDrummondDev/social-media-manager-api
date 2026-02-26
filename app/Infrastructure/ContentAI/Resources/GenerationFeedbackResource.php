<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Resources;

use App\Application\ContentAI\DTOs\GenerationFeedbackOutput;

final readonly class GenerationFeedbackResource
{
    public function __construct(
        private GenerationFeedbackOutput $output,
    ) {}

    public static function fromOutput(GenerationFeedbackOutput $output): self
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
            'type' => 'generation_feedback',
            'attributes' => [
                'generation_id' => $this->output->generationId,
                'action' => $this->output->action,
                'generation_type' => $this->output->generationType,
                'content_id' => $this->output->contentId,
                'time_to_decision_ms' => $this->output->timeToDecisionMs,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
