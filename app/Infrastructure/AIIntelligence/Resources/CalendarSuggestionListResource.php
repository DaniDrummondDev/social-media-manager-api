<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\CalendarSuggestionListOutput;

final readonly class CalendarSuggestionListResource
{
    public function __construct(
        private CalendarSuggestionListOutput $output,
    ) {}

    public static function fromOutput(CalendarSuggestionListOutput $output): self
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
                'item_count' => $this->output->itemCount,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
