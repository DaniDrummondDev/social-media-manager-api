<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Resources;

use App\Application\Campaign\DTOs\ContentOutput;

final readonly class ContentResource
{
    /**
     * @param  string[]  $hashtags
     * @param  array<int, array{provider: string, title: ?string, body: ?string, hashtags: ?string[]}>  $networkOverrides
     * @param  array<int, array{media_id: string, position: int}>  $media
     */
    private function __construct(
        private string $id,
        private string $campaignId,
        private ?string $title,
        private ?string $body,
        private array $hashtags,
        private string $status,
        private ?string $aiGenerationId,
        private array $networkOverrides,
        private array $media,
        private string $createdAt,
        private string $updatedAt,
    ) {}

    public static function fromOutput(ContentOutput $output): self
    {
        return new self(
            id: $output->id,
            campaignId: $output->campaignId,
            title: $output->title,
            body: $output->body,
            hashtags: $output->hashtags,
            status: $output->status,
            aiGenerationId: $output->aiGenerationId,
            networkOverrides: $output->networkOverrides,
            media: $output->media,
            createdAt: $output->createdAt,
            updatedAt: $output->updatedAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'content',
            'attributes' => [
                'title' => $this->title,
                'body' => $this->body,
                'hashtags' => $this->hashtags,
                'status' => $this->status,
                'campaign_id' => $this->campaignId,
                'ai_generation_id' => $this->aiGenerationId,
                'network_overrides' => $this->networkOverrides,
                'media' => $this->media,
                'created_at' => $this->createdAt,
                'updated_at' => $this->updatedAt,
            ],
        ];
    }
}
