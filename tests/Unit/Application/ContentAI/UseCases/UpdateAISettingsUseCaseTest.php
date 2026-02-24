<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\UpdateAISettingsInput;
use App\Application\ContentAI\UseCases\UpdateAISettingsUseCase;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->settingsRepository = Mockery::mock(AISettingsRepositoryInterface::class);
    $this->generationRepository = Mockery::mock(AIGenerationRepositoryInterface::class);

    $this->useCase = new UpdateAISettingsUseCase(
        $this->settingsRepository,
        $this->generationRepository,
    );
});

it('updates existing settings', function () {
    $orgId = Uuid::generate();
    $settings = AISettings::create(organizationId: $orgId);

    $this->settingsRepository->shouldReceive('findByOrganizationId')->once()->andReturn($settings);
    $this->settingsRepository->shouldReceive('upsert')->once();
    $this->generationRepository->shouldReceive('countByOrganizationAndMonth')->once()->andReturn(10);
    $this->generationRepository->shouldReceive('sumUsageByOrganizationAndMonth')->once()->andReturn([
        'tokens_input' => 1000,
        'tokens_output' => 500,
        'cost_estimate' => 0.05,
    ]);

    $output = $this->useCase->execute(new UpdateAISettingsInput(
        organizationId: (string) $orgId,
        defaultTone: 'casual',
        defaultLanguage: 'en_US',
    ));

    expect($output->defaultTone)->toBe('casual')
        ->and($output->defaultLanguage)->toBe('en_US');
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

    $output = $this->useCase->execute(new UpdateAISettingsInput(
        organizationId: (string) $orgId,
        defaultTone: 'fun',
    ));

    expect($output->defaultTone)->toBe('fun');
});
