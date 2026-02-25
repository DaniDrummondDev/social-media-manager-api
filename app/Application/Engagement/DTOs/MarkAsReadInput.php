<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class MarkAsReadInput
{
    /**
     * @param  array<string>  $commentIds
     */
    public function __construct(
        public string $organizationId,
        public array $commentIds,
    ) {}
}
