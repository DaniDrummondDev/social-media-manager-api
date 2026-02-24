<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

use App\Domain\ContentAI\Entities\AISettings;

final readonly class AISettingsOutput
{
    /**
     * @param  array{generations: int, tokens_input: int, tokens_output: int, estimated_cost_usd: float}  $usageThisMonth
     */
    public function __construct(
        public string $defaultTone,
        public ?string $customToneDescription,
        public string $defaultLanguage,
        public int $monthlyGenerationLimit,
        public array $usageThisMonth,
    ) {}

    /**
     * @param  array{generations: int, tokens_input: int, tokens_output: int, estimated_cost_usd: float}  $usageThisMonth
     */
    public static function fromEntity(AISettings $settings, array $usageThisMonth): self
    {
        return new self(
            defaultTone: $settings->defaultTone->value,
            customToneDescription: $settings->customToneDescription,
            defaultLanguage: $settings->defaultLanguage->value,
            monthlyGenerationLimit: $settings->monthlyGenerationLimit,
            usageThisMonth: $usageThisMonth,
        );
    }
}
