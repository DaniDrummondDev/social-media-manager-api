<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class CreatePromptTemplateInput
{
    /**
     * @param  array<string>  $variables
     */
    public function __construct(
        public string $userId,
        public string $generationType,
        public string $version,
        public string $name,
        public string $systemPrompt,
        public string $userPromptTemplate,
        public array $variables = [],
        public bool $isDefault = false,
        public ?string $organizationId = null,
    ) {}
}
