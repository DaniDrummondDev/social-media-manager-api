<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\SafetyRuleOutput;

final readonly class SafetyRuleResource
{
    public function __construct(
        private SafetyRuleOutput $output,
    ) {}

    public static function fromOutput(SafetyRuleOutput $output): self
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
            'type' => 'safety_rule',
            'attributes' => [
                'rule_type' => $this->output->ruleType,
                'rule_config' => $this->output->ruleConfig,
                'severity' => $this->output->severity,
                'is_active' => $this->output->isActive,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
