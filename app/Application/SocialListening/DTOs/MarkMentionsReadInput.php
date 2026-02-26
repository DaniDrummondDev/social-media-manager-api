<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class MarkMentionsReadInput
{
    /**
     * @param  array<string>  $mentionIds
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public array $mentionIds,
    ) {}
}
