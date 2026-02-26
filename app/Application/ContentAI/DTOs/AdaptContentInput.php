<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class AdaptContentInput
{
    /**
     * @param  array<string>  $targetNetworks
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $contentId,
        public string $sourceNetwork,
        public array $targetNetworks,
        public bool $preserveTone = true,
    ) {}
}
