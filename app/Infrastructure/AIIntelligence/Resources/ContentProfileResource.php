<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\ContentProfileOutput;

final readonly class ContentProfileResource
{
    private function __construct(private ContentProfileOutput $output) {}

    public static function fromOutput(ContentProfileOutput $output): self
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
            'type' => 'content_profile',
            'attributes' => [
                'provider' => $this->output->provider,
                'total_contents_analyzed' => $this->output->totalContentsAnalyzed,
                'top_themes' => $this->output->topThemes,
                'engagement_patterns' => $this->output->engagementPatterns,
                'content_fingerprint' => $this->output->contentFingerprint,
                'high_performer_traits' => $this->output->highPerformerTraits,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
