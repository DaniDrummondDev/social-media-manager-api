<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\BrandSafetyCheck;
use App\Domain\AIIntelligence\Events\BrandSafetyBlocked;
use App\Domain\AIIntelligence\Events\BrandSafetyChecked;
use App\Domain\AIIntelligence\Exceptions\SafetyCheckAlreadyCompletedException;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyCategory;
use App\Domain\AIIntelligence\ValueObjects\SafetyCheckResult;
use App\Domain\AIIntelligence\ValueObjects\SafetyStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createCheck(array $overrides = []): BrandSafetyCheck
{
    return BrandSafetyCheck::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        contentId: $overrides['contentId'] ?? Uuid::generate(),
        provider: $overrides['provider'] ?? 'instagram',
    );
}

it('creates with pending status and no events', function () {
    $check = createCheck();

    expect($check->overallStatus)->toBe(SafetyStatus::Pending)
        ->and($check->overallScore)->toBeNull()
        ->and($check->checks)->toBeEmpty()
        ->and($check->modelUsed)->toBeNull()
        ->and($check->tokensInput)->toBeNull()
        ->and($check->tokensOutput)->toBeNull()
        ->and($check->checkedAt)->toBeNull()
        ->and($check->domainEvents)->toBeEmpty();
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $contentId = Uuid::generate();
    $now = new DateTimeImmutable;

    $check = BrandSafetyCheck::reconstitute(
        id: $id,
        organizationId: $orgId,
        contentId: $contentId,
        provider: 'tiktok',
        overallStatus: SafetyStatus::Passed,
        overallScore: 95,
        checks: [],
        modelUsed: 'gpt-4',
        tokensInput: 100,
        tokensOutput: 50,
        checkedAt: $now,
        createdAt: $now,
    );

    expect($check->id)->toEqual($id)
        ->and($check->organizationId)->toEqual($orgId)
        ->and($check->overallStatus)->toBe(SafetyStatus::Passed)
        ->and($check->overallScore)->toBe(95)
        ->and($check->domainEvents)->toBeEmpty();
});

it('completes with passed status and BrandSafetyChecked event', function () {
    $check = createCheck();

    $checks = [
        SafetyCheckResult::create(SafetyCategory::LgpdCompliance, SafetyStatus::Passed),
        SafetyCheckResult::create(SafetyCategory::Profanity, SafetyStatus::Passed),
    ];

    $completed = $check->complete(
        score: 100,
        checks: $checks,
        modelUsed: 'gpt-4',
        tokensInput: 200,
        tokensOutput: 80,
        userId: 'user-1',
    );

    expect($completed->overallStatus)->toBe(SafetyStatus::Passed)
        ->and($completed->overallScore)->toBe(100)
        ->and($completed->checks)->toHaveCount(2)
        ->and($completed->modelUsed)->toBe('gpt-4')
        ->and($completed->checkedAt)->not->toBeNull()
        ->and($completed->domainEvents)->toHaveCount(1)
        ->and($completed->domainEvents[0])->toBeInstanceOf(BrandSafetyChecked::class);
});

it('completes with warning status', function () {
    $check = createCheck();

    $checks = [
        SafetyCheckResult::create(SafetyCategory::LgpdCompliance, SafetyStatus::Passed),
        SafetyCheckResult::create(SafetyCategory::Sensitivity, SafetyStatus::Warning, 'Potentially sensitive content'),
    ];

    $completed = $check->complete(
        score: 70,
        checks: $checks,
        modelUsed: 'gpt-4',
        tokensInput: 200,
        tokensOutput: 80,
        userId: 'user-1',
    );

    expect($completed->overallStatus)->toBe(SafetyStatus::Warning)
        ->and($completed->domainEvents)->toHaveCount(1)
        ->and($completed->domainEvents[0])->toBeInstanceOf(BrandSafetyChecked::class);
});

it('completes with blocked status and emits BrandSafetyBlocked event', function () {
    $check = createCheck();

    $checks = [
        SafetyCheckResult::create(SafetyCategory::LgpdCompliance, SafetyStatus::Passed),
        SafetyCheckResult::create(SafetyCategory::Profanity, SafetyStatus::Blocked, 'Contains profanity'),
    ];

    $completed = $check->complete(
        score: 20,
        checks: $checks,
        modelUsed: 'gpt-4',
        tokensInput: 200,
        tokensOutput: 80,
        userId: 'user-1',
    );

    expect($completed->overallStatus)->toBe(SafetyStatus::Blocked)
        ->and($completed->domainEvents)->toHaveCount(2)
        ->and($completed->domainEvents[0])->toBeInstanceOf(BrandSafetyChecked::class)
        ->and($completed->domainEvents[1])->toBeInstanceOf(BrandSafetyBlocked::class)
        ->and($completed->domainEvents[1]->blockedCategories)->toBe(['profanity']);
});

it('throws SafetyCheckAlreadyCompletedException when already final', function () {
    $check = createCheck();

    $checks = [
        SafetyCheckResult::create(SafetyCategory::LgpdCompliance, SafetyStatus::Passed),
    ];

    $completed = $check->complete(
        score: 100,
        checks: $checks,
        modelUsed: 'gpt-4',
        tokensInput: 100,
        tokensOutput: 50,
        userId: 'user-1',
    );

    $completed->complete(
        score: 100,
        checks: $checks,
        modelUsed: 'gpt-4',
        tokensInput: 100,
        tokensOutput: 50,
        userId: 'user-1',
    );
})->throws(SafetyCheckAlreadyCompletedException::class);

it('isBlocked returns true when status is Blocked', function () {
    $check = BrandSafetyCheck::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        provider: null,
        overallStatus: SafetyStatus::Blocked,
        overallScore: 10,
        checks: [],
        modelUsed: null,
        tokensInput: null,
        tokensOutput: null,
        checkedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
    );

    expect($check->isBlocked())->toBeTrue()
        ->and($check->hasWarnings())->toBeFalse();
});

it('hasWarnings returns true when status is Warning', function () {
    $check = BrandSafetyCheck::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        provider: null,
        overallStatus: SafetyStatus::Warning,
        overallScore: 60,
        checks: [],
        modelUsed: null,
        tokensInput: null,
        tokensOutput: null,
        checkedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
    );

    expect($check->hasWarnings())->toBeTrue()
        ->and($check->isBlocked())->toBeFalse();
});
