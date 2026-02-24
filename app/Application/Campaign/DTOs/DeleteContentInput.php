<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class DeleteContentInput
{
    public function __construct(
        public string $organizationId,
        public string $contentId,
    ) {}
}
