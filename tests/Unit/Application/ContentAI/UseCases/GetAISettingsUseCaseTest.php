<?php

declare(strict_types=1);

use App\Application\ContentAI\UseCases\GetAISettingsUseCase;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->settingsRepository = Mockery::mock(AISettingsRepositoryInterface::class);
    $this->generationRepository = Mockery::mock(AIGenerationRepositoryInterface::class);

    $this->useCase = new GetAISettingsUseCase(
        $this->settingsRepository,
        $this->generationRepository,
    );
});

it('returns existing settings with usage', function () {
    $orgId = Uuid::generate();
    $settings = AISettings::create(organizationId: $orgId);

    $this->settingsRepository->shouldReceive('findByOrganizationId')->once()->andReturn($settings);
    $this->generationRepository->shouldReceive('countByOrganizationAndMonth')->once()->andReturn(25);
    $this->generationRepository->shouldReceive('sumUsageByOrganizationAndMonth')->once()->andReturn([
        'tokens_input' => 3000,
        'tokens_output' => 1500,
        'cost_estimate' => 0.15,
    ]);

    $output = $this->useCase->execute((string) $orgId);

    expect($output->defaultTone)->toBe('professional')
        ->and($output->monthlyGenerationLimit)->toBe(500)
        ->and($output->usageThisMonth['generations'])->toBe(25);
});

it('creates default settings when none exist', function () {
    $orgId = Uuid::generate();

    $this->settingsRepository->shouldReceive('findByOrganizationId')->once()->andReturnNull();
    $this->settingsRepository->shouldReceive('upsert')->once();
    $this->generationRepository->shouldReceive('countByOrganizationAndMonth')->once()->andReturn(0);
    $this->generationRepository->shouldReceive('sumUsageByOrganizationAndMonth')->once()->andReturn([
        'tokens_input' => 0,
        'tokens_output' => 0,
        'cost_estimate' => 0.0,
    ]);

    $output = $this->useCase->execute((string) $orgId);

    expect($output->defaultTone)->toBe('professional')
        ->and($output->defaultLanguage)->toBe('pt_BR');
});
