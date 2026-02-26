<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\SafetyRuleOutput;
use App\Application\AIIntelligence\DTOs\UpdateSafetyRuleInput;
use App\Application\AIIntelligence\Exceptions\SafetyRuleNotFoundException;
use App\Application\AIIntelligence\UseCases\UpdateSafetyRuleUseCase;
use App\Domain\AIIntelligence\Entities\BrandSafetyRule;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyRuleType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->ruleRepository = Mockery::mock(BrandSafetyRuleRepositoryInterface::class);

    $this->useCase = new UpdateSafetyRuleUseCase(
        $this->ruleRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->ruleId = 'b1c2d3e4-f5a6-7890-bcde-f12345678901';
});

it('updates rule successfully', function () {
    $existingRule = BrandSafetyRule::reconstitute(
        id: Uuid::fromString($this->ruleId),
        organizationId: Uuid::fromString($this->orgId),
        ruleType: SafetyRuleType::BlockedWord,
        ruleConfig: ['words' => ['spam']],
        severity: RuleSeverity::Warning,
        isActive: true,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->ruleRepository->shouldReceive('findById')->once()->andReturn($existingRule);
    $this->ruleRepository->shouldReceive('update')->once();

    $input = new UpdateSafetyRuleInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        ruleId: $this->ruleId,
        severity: 'block',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(SafetyRuleOutput::class)
        ->and($output->severity)->toBe('block')
        ->and($output->ruleType)->toBe('blocked_word');
});

it('throws SafetyRuleNotFoundException when not found', function () {
    $this->ruleRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new UpdateSafetyRuleInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        ruleId: $this->ruleId,
    );

    $this->useCase->execute($input);
})->throws(SafetyRuleNotFoundException::class);

it('throws SafetyRuleNotFoundException when wrong organization', function () {
    $differentOrgId = 'c3d4e5f6-a7b8-9012-cdef-345678901234';

    $existingRule = BrandSafetyRule::reconstitute(
        id: Uuid::fromString($this->ruleId),
        organizationId: Uuid::fromString($differentOrgId),
        ruleType: SafetyRuleType::BlockedWord,
        ruleConfig: ['words' => ['spam']],
        severity: RuleSeverity::Warning,
        isActive: true,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->ruleRepository->shouldReceive('findById')->once()->andReturn($existingRule);

    $input = new UpdateSafetyRuleInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        ruleId: $this->ruleId,
    );

    $this->useCase->execute($input);
})->throws(SafetyRuleNotFoundException::class);
