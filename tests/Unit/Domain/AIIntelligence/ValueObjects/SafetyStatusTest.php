<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\SafetyStatus;

it('isFinal returns false for Pending', function () {
    expect(SafetyStatus::Pending->isFinal())->toBeFalse();
});

it('isFinal returns true for Passed', function () {
    expect(SafetyStatus::Passed->isFinal())->toBeTrue();
});

it('isFinal returns true for Warning', function () {
    expect(SafetyStatus::Warning->isFinal())->toBeTrue();
});

it('isFinal returns true for Blocked', function () {
    expect(SafetyStatus::Blocked->isFinal())->toBeTrue();
});
