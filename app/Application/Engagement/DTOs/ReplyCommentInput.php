<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ReplyCommentInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $commentId,
        public string $text,
    ) {}
}
