<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Adapters;

use App\Domain\SocialAccount\Contracts\SocialPublisherInterface;

final class TikTokPublisher implements SocialPublisherInterface
{
    public function publish(array $params): array
    {
        throw new \RuntimeException('TikTok publisher not yet implemented.');
    }

    public function getPostStatus(string $externalPostId): array
    {
        throw new \RuntimeException('TikTok publisher not yet implemented.');
    }

    public function deletePost(string $externalPostId): void
    {
        throw new \RuntimeException('TikTok publisher not yet implemented.');
    }
}
