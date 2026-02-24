<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Contracts;

interface SocialEngagementInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getComments(string $externalPostId, ?string $cursor = null): array;

    /**
     * @return array<string, mixed>
     */
    public function replyToComment(string $externalCommentId, string $text): array;

    public function deleteComment(string $externalCommentId): void;
}
