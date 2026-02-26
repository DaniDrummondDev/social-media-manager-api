<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

enum ConditionType: string
{
    case VolumeSpike = 'volume_spike';
    case NegativeSentimentSpike = 'negative_sentiment_spike';
    case KeywordDetected = 'keyword_detected';
    case InfluencerMention = 'influencer_mention';
}
