<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Resources;

use App\Application\SocialListening\DTOs\MentionOutput;

final readonly class MentionResource
{
    public function __construct(
        private MentionOutput $output,
    ) {}

    public static function fromOutput(MentionOutput $output): self
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
            'type' => 'mention',
            'attributes' => [
                'query_id' => $this->output->queryId,
                'platform' => $this->output->platform,
                'external_id' => $this->output->externalId,
                'author_username' => $this->output->authorUsername,
                'author_display_name' => $this->output->authorDisplayName,
                'author_follower_count' => $this->output->authorFollowerCount,
                'profile_url' => $this->output->profileUrl,
                'content' => $this->output->content,
                'url' => $this->output->url,
                'sentiment' => $this->output->sentiment,
                'sentiment_score' => $this->output->sentimentScore,
                'reach' => $this->output->reach,
                'engagement_count' => $this->output->engagementCount,
                'is_flagged' => $this->output->isFlagged,
                'is_read' => $this->output->isRead,
                'published_at' => $this->output->publishedAt,
                'detected_at' => $this->output->detectedAt,
            ],
        ];
    }
}
