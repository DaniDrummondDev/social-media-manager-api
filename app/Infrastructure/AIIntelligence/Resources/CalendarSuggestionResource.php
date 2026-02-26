<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\CalendarSuggestionOutput;

final readonly class CalendarSuggestionResource
{
    public function __construct(
        private CalendarSuggestionOutput $output,
    ) {}

    public static function fromOutput(CalendarSuggestionOutput $output): self
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
            'type' => 'calendar_suggestion',
            'attributes' => [
                'period_start' => $this->output->periodStart,
                'period_end' => $this->output->periodEnd,
                'status' => $this->output->status,
                'suggestions' => $this->output->suggestions,
                'based_on' => $this->output->basedOn,
                'accepted_items' => $this->output->acceptedItems,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
