<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\InsightType;

it('has all expected cases', function () {
    expect(InsightType::cases())->toHaveCount(4);
});

it('creates from string value', function () {
    expect(InsightType::from('preferred_topics'))->toBe(InsightType::PreferredTopics)
        ->and(InsightType::from('sentiment_trends'))->toBe(InsightType::SentimentTrends)
        ->and(InsightType::from('engagement_drivers'))->toBe(InsightType::EngagementDrivers)
        ->and(InsightType::from('audience_preferences'))->toBe(InsightType::AudiencePreferences);
});

it('throws ValueError for invalid type', function () {
    InsightType::from('invalid_type');
})->throws(ValueError::class);
