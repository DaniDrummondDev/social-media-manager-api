<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

use App\Domain\ContentAI\Entities\PromptTemplate;

final readonly class PromptTemplateOutput
{
    /**
     * @param  array<string>  $variables
     */
    public function __construct(
        public string $id,
        public ?string $organizationId,
        public string $generationType,
        public string $version,
        public string $name,
        public string $systemPrompt,
        public string $userPromptTemplate,
        public array $variables,
        public bool $isActive,
        public bool $isDefault,
        public ?float $performanceScore,
        public int $totalUses,
        public int $totalAccepted,
        public int $totalEdited,
        public int $totalRejected,
        public ?string $createdBy,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(PromptTemplate $template): self
    {
        return new self(
            id: (string) $template->id,
            organizationId: $template->organizationId !== null ? (string) $template->organizationId : null,
            generationType: $template->generationType,
            version: $template->version,
            name: $template->name,
            systemPrompt: $template->systemPrompt,
            userPromptTemplate: $template->userPromptTemplate,
            variables: $template->variables,
            isActive: $template->isActive,
            isDefault: $template->isDefault,
            performanceScore: $template->performanceScore?->value,
            totalUses: $template->totalUses,
            totalAccepted: $template->totalAccepted,
            totalEdited: $template->totalEdited,
            totalRejected: $template->totalRejected,
            createdBy: $template->createdBy !== null ? (string) $template->createdBy : null,
            createdAt: $template->createdAt->format('c'),
            updatedAt: $template->updatedAt->format('c'),
        );
    }
}
