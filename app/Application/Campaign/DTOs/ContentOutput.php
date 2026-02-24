<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Entities\ContentNetworkOverride;

final readonly class ContentOutput
{
    /**
     * @param  string[]  $hashtags
     * @param  array<int, array{provider: string, title: ?string, body: ?string, hashtags: ?string[]}>  $networkOverrides
     * @param  array<int, array{media_id: string, position: int}>  $media
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $campaignId,
        public ?string $title,
        public ?string $body,
        public array $hashtags,
        public string $status,
        public ?string $aiGenerationId,
        public array $networkOverrides,
        public array $media,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * @param  ContentNetworkOverride[]  $overrides
     * @param  array<int, array{media_id: string, position: int}>  $media
     */
    public static function fromEntity(Content $content, array $overrides = [], array $media = []): self
    {
        $overrideData = array_map(fn (ContentNetworkOverride $o) => [
            'provider' => $o->provider->value,
            'title' => $o->title,
            'body' => $o->body,
            'hashtags' => $o->hashtags,
        ], $overrides);

        return new self(
            id: (string) $content->id,
            organizationId: (string) $content->organizationId,
            campaignId: (string) $content->campaignId,
            title: $content->title,
            body: $content->body,
            hashtags: $content->hashtags,
            status: $content->status->value,
            aiGenerationId: $content->aiGenerationId,
            networkOverrides: array_values($overrideData),
            media: $media,
            createdAt: $content->createdAt->format('c'),
            updatedAt: $content->updatedAt->format('c'),
        );
    }
}
