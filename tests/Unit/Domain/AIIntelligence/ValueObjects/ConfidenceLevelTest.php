<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;

it('returns Low when sample size < 20', function () {
    expect(ConfidenceLevel::fromSampleSize(0))->toBe(ConfidenceLevel::Low)
        ->and(ConfidenceLevel::fromSampleSize(10))->toBe(ConfidenceLevel::Low)
        ->and(ConfidenceLevel::fromSampleSize(19))->toBe(ConfidenceLevel::Low);
});

it('returns Medium when sample size 20-50', function () {
    expect(ConfidenceLevel::fromSampleSize(20))->toBe(ConfidenceLevel::Medium)
        ->and(ConfidenceLevel::fromSampleSize(35))->toBe(ConfidenceLevel::Medium)
        ->and(ConfidenceLevel::fromSampleSize(50))->toBe(ConfidenceLevel::Medium);
});

it('returns High when sample size > 50', function () {
    expect(ConfidenceLevel::fromSampleSize(51))->toBe(ConfidenceLevel::High)
        ->and(ConfidenceLevel::fromSampleSize(100))->toBe(ConfidenceLevel::High)
        ->and(ConfidenceLevel::fromSampleSize(1000))->toBe(ConfidenceLevel::High);
});
