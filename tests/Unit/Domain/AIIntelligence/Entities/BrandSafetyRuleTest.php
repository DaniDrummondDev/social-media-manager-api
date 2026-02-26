<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\BrandSafetyRule;
use App\Domain\AIIntelligence\Exceptions\InvalidSafetyRuleConfigException;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyRuleType;
use App\Domain\Shared\ValueObjects\Uuid;

function createRule(array $overrides = []): BrandSafetyRule
{
    return BrandSafetyRule::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        ruleType: $overrides['ruleType'] ?? SafetyRuleType::BlockedWord,
        ruleConfig: $overrides['ruleConfig'] ?? ['words' => ['spam', 'offensive']],
        severity: $overrides['severity'] ?? RuleSeverity::Warning,
    );
}

it('creates blocked_word rule', function () {
    $rule = createRule();

    expect($rule->ruleType)->toBe(SafetyRuleType::BlockedWord)
        ->and($rule->ruleConfig)->toBe(['words' => ['spam', 'offensive']])
        ->and($rule->severity)->toBe(RuleSeverity::Warning)
        ->and($rule->isActive)->toBeTrue();
});

it('creates required_disclosure rule', function () {
    $rule = createRule([
        'ruleType' => SafetyRuleType::RequiredDisclosure,
        'ruleConfig' => [
            'keywords' => ['patrocinado', 'parceria'],
            'disclosure_text' => '#publi',
        ],
    ]);

    expect($rule->ruleType)->toBe(SafetyRuleType::RequiredDisclosure)
        ->and($rule->ruleConfig['keywords'])->toBe(['patrocinado', 'parceria'])
        ->and($rule->ruleConfig['disclosure_text'])->toBe('#publi');
});

it('throws InvalidSafetyRuleConfigException with empty words', function () {
    createRule([
        'ruleType' => SafetyRuleType::BlockedWord,
        'ruleConfig' => ['words' => []],
    ]);
})->throws(InvalidSafetyRuleConfigException::class);

it('throws InvalidSafetyRuleConfigException with missing words key', function () {
    createRule([
        'ruleType' => SafetyRuleType::BlockedWord,
        'ruleConfig' => [],
    ]);
})->throws(InvalidSafetyRuleConfigException::class);

it('throws InvalidSafetyRuleConfigException with missing disclosure_text', function () {
    createRule([
        'ruleType' => SafetyRuleType::RequiredDisclosure,
        'ruleConfig' => ['keywords' => ['patrocinado']],
    ]);
})->throws(InvalidSafetyRuleConfigException::class);

it('throws InvalidSafetyRuleConfigException with empty keywords', function () {
    createRule([
        'ruleType' => SafetyRuleType::RequiredDisclosure,
        'ruleConfig' => ['keywords' => [], 'disclosure_text' => '#publi'],
    ]);
})->throws(InvalidSafetyRuleConfigException::class);

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $now = new DateTimeImmutable;

    $rule = BrandSafetyRule::reconstitute(
        id: $id,
        organizationId: $orgId,
        ruleType: SafetyRuleType::BlockedWord,
        ruleConfig: ['words' => ['test']],
        severity: RuleSeverity::Block,
        isActive: false,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($rule->id)->toEqual($id)
        ->and($rule->isActive)->toBeFalse()
        ->and($rule->severity)->toBe(RuleSeverity::Block)
        ->and($rule->domainEvents)->toBeEmpty();
});

it('updates type and validates new config', function () {
    $rule = createRule();

    $updated = $rule->update(
        ruleType: SafetyRuleType::RequiredDisclosure,
        ruleConfig: [
            'keywords' => ['sponsored'],
            'disclosure_text' => '#ad',
        ],
    );

    expect($updated->ruleType)->toBe(SafetyRuleType::RequiredDisclosure)
        ->and($updated->ruleConfig['disclosure_text'])->toBe('#ad')
        ->and($updated->id)->toEqual($rule->id);
});

it('updates only severity without re-validating config', function () {
    $rule = createRule();

    $updated = $rule->update(severity: RuleSeverity::Block);

    expect($updated->severity)->toBe(RuleSeverity::Block)
        ->and($updated->ruleType)->toBe($rule->ruleType)
        ->and($updated->ruleConfig)->toBe($rule->ruleConfig);
});

it('activates a deactivated rule', function () {
    $rule = createRule();
    $deactivated = $rule->deactivate();
    $activated = $deactivated->activate();

    expect($deactivated->isActive)->toBeFalse()
        ->and($activated->isActive)->toBeTrue();
});

it('deactivates an active rule', function () {
    $rule = createRule();
    $deactivated = $rule->deactivate();

    expect($rule->isActive)->toBeTrue()
        ->and($deactivated->isActive)->toBeFalse();
});

it('matches blocked word case insensitively', function () {
    $rule = createRule(['ruleConfig' => ['words' => ['SPAM', 'offensive']]]);

    expect($rule->matches('This is spam content'))->toBeTrue()
        ->and($rule->matches('This is SPAM content'))->toBeTrue()
        ->and($rule->matches('Very Offensive post'))->toBeTrue();
});

it('does not match when no blocked word found', function () {
    $rule = createRule(['ruleConfig' => ['words' => ['spam', 'offensive']]]);

    expect($rule->matches('This is clean content'))->toBeFalse();
});

it('matches missing required disclosure', function () {
    $rule = createRule([
        'ruleType' => SafetyRuleType::RequiredDisclosure,
        'ruleConfig' => [
            'keywords' => ['patrocinado', 'parceria'],
            'disclosure_text' => '#publi',
        ],
    ]);

    expect($rule->matches('Post patrocinado sem divulgação'))->toBeTrue();
});

it('does not match when required disclosure is present', function () {
    $rule = createRule([
        'ruleType' => SafetyRuleType::RequiredDisclosure,
        'ruleConfig' => [
            'keywords' => ['patrocinado'],
            'disclosure_text' => '#publi',
        ],
    ]);

    expect($rule->matches('Post patrocinado #publi'))->toBeFalse();
});

it('does not match when no keyword is found for required disclosure', function () {
    $rule = createRule([
        'ruleType' => SafetyRuleType::RequiredDisclosure,
        'ruleConfig' => [
            'keywords' => ['patrocinado'],
            'disclosure_text' => '#publi',
        ],
    ]);

    expect($rule->matches('Normal post without keywords'))->toBeFalse();
});

it('returns false for custom_check type', function () {
    $rule = BrandSafetyRule::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        ruleType: SafetyRuleType::CustomCheck,
        ruleConfig: ['prompt' => 'Check this'],
        severity: RuleSeverity::Warning,
        isActive: true,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect($rule->matches('Any content here'))->toBeFalse();
});
