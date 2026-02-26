<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\CreateSafetyRuleInput;
use App\Application\AIIntelligence\DTOs\SafetyRuleOutput;
use App\Application\AIIntelligence\UseCases\CreateSafetyRuleUseCase;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;

beforeEach(function () {
    $this->ruleRepository = Mockery::mock(BrandSafetyRuleRepositoryInterface::class);

    $this->useCase = new CreateSafetyRuleUseCase(
        $this->ruleRepository,
    );
});

it('creates rule and returns SafetyRuleOutput', function () {
    $this->ruleRepository->shouldReceive('create')->once();

    $input = new CreateSafetyRuleInput(
        organizationId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        ruleType: 'blocked_word',
        ruleConfig: ['words' => ['spam', 'offensive']],
        severity: 'warning',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(SafetyRuleOutput::class)
        ->and($output->ruleType)->toBe('blocked_word')
        ->and($output->ruleConfig)->toBe(['words' => ['spam', 'offensive']])
        ->and($output->severity)->toBe('warning')
        ->and($output->isActive)->toBeTrue();
});
