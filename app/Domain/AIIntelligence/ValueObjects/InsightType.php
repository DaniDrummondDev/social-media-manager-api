<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum InsightType: string
{
    case PreferredTopics = 'preferred_topics';
    case SentimentTrends = 'sentiment_trends';
    case EngagementDrivers = 'engagement_drivers';
    case AudiencePreferences = 'audience_preferences';
}
