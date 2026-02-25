<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Adapters;

use App\Domain\SocialAccount\Contracts\SocialEngagementInterface;
use RuntimeException;

final class YouTubeEngagement implements SocialEngagementInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getComments(string $externalPostId, ?string $cursor = null): array
    {
        throw new RuntimeException('YouTube engagement adapter not implemented yet.');
    }

    /**
     * @return array<string, mixed>
     */
    public function replyToComment(string $externalCommentId, string $text): array
    {
        throw new RuntimeException('YouTube engagement adapter not implemented yet.');
    }

    public function deleteComment(string $externalCommentId): void
    {
        throw new RuntimeException('YouTube engagement adapter not implemented yet.');
    }
}
