<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\StyleProfileOutput;

final readonly class StyleProfileResource
{
    public function __construct(
        private StyleProfileOutput $output,
    ) {}

    public static function fromOutput(StyleProfileOutput $output): self
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
            'type' => 'style_profile',
            'attributes' => [
                'generation_type' => $this->output->generationType,
                'sample_size' => $this->output->sampleSize,
                'confidence_level' => $this->output->confidenceLevel,
                'tone_preferences' => $this->output->tonePreferences,
                'length_preferences' => $this->output->lengthPreferences,
                'vocabulary_preferences' => $this->output->vocabularyPreferences,
                'structure_preferences' => $this->output->structurePreferences,
                'hashtag_preferences' => $this->output->hashtagPreferences,
                'style_summary' => $this->output->styleSummary,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
