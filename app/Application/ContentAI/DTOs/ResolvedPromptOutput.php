<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class ResolvedPromptOutput
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

    public static function fromResult(ResolvedPromptResult $result): self
    {
        return new self(
            templateId: $result->templateId,
            experimentId: $result->experimentId,
            systemPrompt: $result->systemPrompt,
            userPromptTemplate: $result->userPromptTemplate,
            variables: $result->variables,
            variantSelected: $result->variantSelected,
        );
    }
}
