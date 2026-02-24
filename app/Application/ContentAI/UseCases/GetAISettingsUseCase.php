<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\AISettingsOutput;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GetAISettingsUseCase
{
    public function __construct(
        private readonly AISettingsRepositoryInterface $settingsRepository,
        private readonly AIGenerationRepositoryInterface $generationRepository,
    ) {}

    public function execute(string $organizationId): AISettingsOutput
    {
        $orgId = Uuid::fromString($organizationId);
        $settings = $this->settingsRepository->findByOrganizationId($orgId);

        if ($settings === null) {
            $settings = AISettings::create(organizationId: $orgId);
            $this->settingsRepository->upsert($settings);
        }

        $now = new DateTimeImmutable;
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
