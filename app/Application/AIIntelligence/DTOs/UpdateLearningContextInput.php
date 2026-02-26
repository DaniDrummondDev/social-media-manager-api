<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class UpdateLearningContextInput
{
    /**
     * @param  array<string>  $contextTypes  e.g. ['rag_examples', 'org_style']
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public array $contextTypes,
    ) {}
}
