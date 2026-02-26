<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class ResolvedPromptResult
{
    /**
     * @param  array<string>  $variables
     */
    public function __construct(
        public string $templateId,
        public ?string $experimentId,
        public string $systemPrompt,
        public string $userPromptTemplate,
        public array $variables,
        public ?string $variantSelected,
    ) {}
}
