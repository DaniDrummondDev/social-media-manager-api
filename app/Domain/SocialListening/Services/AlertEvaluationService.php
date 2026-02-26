<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Services;

use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\Sentiment;

final readonly class AlertEvaluationService
{
    /**
     * Evaluate whether an alert condition is met based on recent mentions.
     *
     * @param  array<Mention>  $recentMentions  Mentions within the alert's time window
     * @param  int  $previousPeriodCount  Number of mentions in the equivalent previous period
     */
    public function evaluate(ListeningAlert $alert, array $recentMentions, int $previousPeriodCount): bool
    {
        if (! $alert->canTrigger()) {
            return false;
        }

        return match ($alert->condition->type) {
            ConditionType::VolumeSpike => $this->evaluateVolumeSpike($recentMentions, $previousPeriodCount, $alert->condition->threshold),
            ConditionType::NegativeSentimentSpike => $this->evaluateNegativeSentimentSpike($recentMentions, $alert->condition->threshold),
            ConditionType::KeywordDetected => false,
            ConditionType::InfluencerMention => $this->evaluateInfluencerMention($recentMentions, $alert->condition->threshold),
        };
    }

    /**
     * @param  array<Mention>  $recentMentions
     */
    private function evaluateVolumeSpike(array $recentMentions, int $previousPeriodCount, int $threshold): bool
    {
        $currentCount = count($recentMentions);

        if ($previousPeriodCount === 0) {
            return $currentCount >= $threshold;
        }

        $increasePercentage = (($currentCount - $previousPeriodCount) / $previousPeriodCount) * 100;

        return $increasePercentage >= $threshold;
    }

    /**
     * @param  array<Mention>  $recentMentions
     */
    private function evaluateNegativeSentimentSpike(array $recentMentions, int $threshold): bool
    {
        if (count($recentMentions) === 0) {
            return false;
        }

        $negativeCount = 0;
        foreach ($recentMentions as $mention) {
            if ($mention->sentiment === Sentiment::Negative) {
                $negativeCount++;
            }
        }

        $negativePercentage = ($negativeCount / count($recentMentions)) * 100;

        return $negativePercentage >= $threshold;
    }

    /**
     * @param  array<Mention>  $recentMentions
     */
    private function evaluateInfluencerMention(array $recentMentions, int $followerThreshold): bool
    {
        foreach ($recentMentions as $mention) {
            if ($mention->authorFollowerCount !== null && $mention->authorFollowerCount >= $followerThreshold) {
                return true;
            }
        }

        return false;
    }
}
