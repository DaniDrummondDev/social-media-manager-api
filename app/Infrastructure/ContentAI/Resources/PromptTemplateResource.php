<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Resources;

use App\Application\ContentAI\DTOs\PromptTemplateOutput;

final readonly class PromptTemplateResource
{
    public function __construct(
        private PromptTemplateOutput $output,
    ) {}

    public static function fromOutput(PromptTemplateOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'prompt_template',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'generation_type' => $this->output->generationType,
                'version' => $this->output->version,
                'name' => $this->output->name,
                'system_prompt' => $this->output->systemPrompt,
                'user_prompt_template' => $this->output->userPromptTemplate,
                'variables' => $this->output->variables,
                'is_active' => $this->output->isActive,
                'is_default' => $this->output->isDefault,
                'performance_score' => $this->output->performanceScore,
                'total_uses' => $this->output->totalUses,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
