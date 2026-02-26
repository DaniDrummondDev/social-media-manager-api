<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyCategory;
use App\Domain\AIIntelligence\ValueObjects\SafetyCheckResult;
use App\Domain\AIIntelligence\ValueObjects\SafetyStatus;

it('creates with all fields', function () {
    $result = SafetyCheckResult::create(
        category: SafetyCategory::Profanity,
        status: SafetyStatus::Blocked,
        message: 'Contains profanity',
        severity: RuleSeverity::Block,
    );

    expect($result->category)->toBe(SafetyCategory::Profanity)
        ->and($result->status)->toBe(SafetyStatus::Blocked)
        ->and($result->message)->toBe('Contains profanity')
        ->and($result->severity)->toBe(RuleSeverity::Block);
});

it('creates with null message and severity', function () {
    $result = SafetyCheckResult::create(
        category: SafetyCategory::LgpdCompliance,
        status: SafetyStatus::Passed,
    );

    expect($result->message)->toBeNull()
        ->and($result->severity)->toBeNull();
});

it('round-trips via fromArray and toArray', function () {
    $original = SafetyCheckResult::create(
        category: SafetyCategory::AdvertisingDisclosure,
        status: SafetyStatus::Warning,
        message: 'Missing disclosure',
        severity: RuleSeverity::Warning,
    );

    $array = $original->toArray();
    $restored = SafetyCheckResult::fromArray($array);

    expect($restored->category)->toBe($original->category)
        ->and($restored->status)->toBe($original->status)
        ->and($restored->message)->toBe($original->message)
        ->and($restored->severity)->toBe($original->severity);
});

it('round-trips with null values via fromArray and toArray', function () {
    $original = SafetyCheckResult::create(
        category: SafetyCategory::PlatformPolicy,
        status: SafetyStatus::Passed,
    );

    $array = $original->toArray();
    $restored = SafetyCheckResult::fromArray($array);

    expect($restored->message)->toBeNull()
        ->and($restored->severity)->toBeNull();
});
