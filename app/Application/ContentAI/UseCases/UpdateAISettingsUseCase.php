<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\AISettingsOutput;
use App\Application\ContentAI\DTOs\UpdateAISettingsInput;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\ContentAI\ValueObjects\Language;
use App\Domain\ContentAI\ValueObjects\Tone;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateAISettingsUseCase
{
    public function __construct(
        private readonly AISettingsRepositoryInterface $settingsRepository,
        private readonly AIGenerationRepositoryInterface $generationRepository,
    ) {}

    public function execute(UpdateAISettingsInput $input): AISettingsOutput
    {
        $orgId = Uuid::fromString($input->organizationId);
        $settings = $this->settingsRepository->findByOrganizationId($orgId);

        if ($settings === null) {
            $settings = AISettings::create(
                organizationId: $orgId,
                defaultTone: $input->defaultTone !== null ? Tone::from($input->defaultTone) : Tone::Professional,
                customToneDescription: $input->customToneDescription,
                defaultLanguage: $input->defaultLanguage !== null ? Language::from($input->defaultLanguage) : Language::PtBR,
            );
        } else {
            $settings = $settings->update(
                defaultTone: $input->defaultTone !== null ? Tone::from($input->defaultTone) : null,
                customToneDescription: $input->customToneDescription,
                defaultLanguage: $input->defaultLanguage !== null ? Language::from($input->defaultLanguage) : null,
            );
        }

        $this->settingsRepository->upsert($settings);

        $now = new \DateTimeImmutable;
        $usage = $this->generationRepository->sumUsageByOrganizationAndMonth($orgId, (int) $now->format('Y'), (int) $now->format('n'));
        $count = $this->generationRepository->countByOrganizationAndMonth($orgId, (int) $now->format('Y'), (int) $now->format('n'));

        return AISettingsOutput::fromEntity($settings, [
            'generations' => $count,
            'tokens_input' => $usage['tokens_input'],
            'tokens_output' => $usage['tokens_output'],
            'estimated_cost_usd' => $usage['cost_estimate'],
        ]);
    }
}
