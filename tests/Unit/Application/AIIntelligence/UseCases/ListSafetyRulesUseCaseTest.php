<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\ListSafetyRulesInput;
use App\Application\AIIntelligence\DTOs\SafetyRuleOutput;
use App\Application\AIIntelligence\UseCases\ListSafetyRulesUseCase;
use App\Domain\AIIntelligence\Entities\BrandSafetyRule;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyRuleType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->ruleRepository = Mockery::mock(BrandSafetyRuleRepositoryInterface::class);

    $this->useCase = new ListSafetyRulesUseCase(
        $this->ruleRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns paginated rules', function () {
    $rule = BrandSafetyRule::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        ruleType: SafetyRuleType::BlockedWord,
        ruleConfig: ['words' => ['spam']],
        severity: RuleSeverity::Warning,
        isActive: true,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->ruleRepository->shouldReceive('findByOrganizationId')->once()->andReturn([
        'items' => [$rule],
        'next_cursor' => 'cursor-abc',
    ]);

    $input = new ListSafetyRulesInput(
        organizationId: $this->orgId,
        limit: 20,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toBeInstanceOf(SafetyRuleOutput::class)
        ->and($result['items'][0]->ruleType)->toBe('blocked_word')
        ->and($result['next_cursor'])->toBe('cursor-abc');
});

it('returns empty result when no rules', function () {
    $this->ruleRepository->shouldReceive('findByOrganizationId')->once()->andReturn([
        'items' => [],
        'next_cursor' => null,
    ]);

    $input = new ListSafetyRulesInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toBeEmpty()
        ->and($result['next_cursor'])->toBeNull();
});
