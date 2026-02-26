<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class ResolvePromptTemplateInput
{
    public function __construct(
        public string $organizationId,
        public string $generationType,
    ) {}
}
