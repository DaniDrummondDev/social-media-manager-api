<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Resources;

use App\Application\Analytics\DTOs\GetContentAnalyticsOutput;

final readonly class ContentAnalyticsResource
{
    private function __construct(
        private GetContentAnalyticsOutput $output,
    ) {}

    public static function fromOutput(GetContentAnalyticsOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content_id' => $this->output->contentId,
            'title' => $this->output->title,
            'campaign_name' => $this->output->campaignName,
            'published_at' => $this->output->publishedAt,
            'networks' => $this->output->networks,
            'last_synced_at' => $this->output->lastSyncedAt,
        ];
    }
}
