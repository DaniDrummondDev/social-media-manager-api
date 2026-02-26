<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Services\AlertEvaluationService;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;
use App\Domain\SocialListening\ValueObjects\Sentiment;

function createAlertMention(array $overrides = []): Mention
{
    return Mention::create(
        queryId: $overrides['queryId'] ?? Uuid::generate(),
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        platform: $overrides['platform'] ?? 'instagram',
        externalId: $overrides['externalId'] ?? 'ext-' . uniqid(),
        authorUsername: $overrides['authorUsername'] ?? 'user',
        authorDisplayName: $overrides['authorDisplayName'] ?? 'User',
        authorFollowerCount: $overrides['authorFollowerCount'] ?? null,
        profileUrl: $overrides['profileUrl'] ?? null,
        content: $overrides['content'] ?? 'Some mention content',
        url: $overrides['url'] ?? null,
        sentiment: $overrides['sentiment'] ?? Sentiment::Neutral,
        sentimentScore: $overrides['sentimentScore'] ?? 0.5,
        reach: $overrides['reach'] ?? 100,
        engagementCount: $overrides['engagementCount'] ?? 10,
        publishedAt: $overrides['publishedAt'] ?? new DateTimeImmutable,
    );
}

function createVolumeSpikeAlert(int $threshold = 50): ListeningAlert
{
    return ListeningAlert::create(
        organizationId: Uuid::generate(),
        name: 'Volume Spike Alert',
        queryIds: ['query-1'],
        condition: AlertCondition::create(ConditionType::VolumeSpike, $threshold, 60),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 30,
        userId: 'user-1',
    );
}

function createNegativeSentimentAlert(int $threshold = 40): ListeningAlert
{
    return ListeningAlert::create(
        organizationId: Uuid::generate(),
        name: 'Negative Sentiment Alert',
        queryIds: ['query-1'],
        condition: AlertCondition::create(ConditionType::NegativeSentimentSpike, $threshold, 60),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 30,
        userId: 'user-1',
    );
}

function createInfluencerAlert(int $followerThreshold = 10000): ListeningAlert
{
    return ListeningAlert::create(
        organizationId: Uuid::generate(),
        name: 'Influencer Alert',
        queryIds: ['query-1'],
        condition: AlertCondition::create(ConditionType::InfluencerMention, $followerThreshold, 60),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 30,
        userId: 'user-1',
    );
}

it('detects volume spike when increase exceeds threshold', function () {
    $service = new AlertEvaluationService;
    $alert = createVolumeSpikeAlert(threshold: 50);

    // 15 recent mentions vs 10 previous = 50% increase
    $mentions = array_map(fn () => createAlertMention(), range(1, 15));

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 10);

    expect($result)->toBeTrue();
});

it('does not detect volume spike below threshold', function () {
    $service = new AlertEvaluationService;
    $alert = createVolumeSpikeAlert(threshold: 50);

    // 12 recent mentions vs 10 previous = 20% increase (below 50%)
    $mentions = array_map(fn () => createAlertMention(), range(1, 12));

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 10);

    expect($result)->toBeFalse();
});

it('detects volume spike with zero previous (threshold as absolute)', function () {
    $service = new AlertEvaluationService;
    $alert = createVolumeSpikeAlert(threshold: 5);

    // 5 recent mentions vs 0 previous, threshold is 5 as absolute count
    $mentions = array_map(fn () => createAlertMention(), range(1, 5));

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 0);

    expect($result)->toBeTrue();
});

it('detects negative sentiment spike above threshold', function () {
    $service = new AlertEvaluationService;
    $alert = createNegativeSentimentAlert(threshold: 40);

    // 5 out of 10 mentions are negative = 50% (above 40%)
    $mentions = [];
    for ($i = 0; $i < 5; $i++) {
        $mentions[] = createAlertMention(['sentiment' => Sentiment::Negative]);
    }
    for ($i = 0; $i < 5; $i++) {
        $mentions[] = createAlertMention(['sentiment' => Sentiment::Positive]);
    }

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 0);

    expect($result)->toBeTrue();
});

it('does not detect negative sentiment spike below threshold', function () {
    $service = new AlertEvaluationService;
    $alert = createNegativeSentimentAlert(threshold: 40);

    // 2 out of 10 mentions are negative = 20% (below 40%)
    $mentions = [];
    for ($i = 0; $i < 2; $i++) {
        $mentions[] = createAlertMention(['sentiment' => Sentiment::Negative]);
    }
    for ($i = 0; $i < 8; $i++) {
        $mentions[] = createAlertMention(['sentiment' => Sentiment::Positive]);
    }

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 0);

    expect($result)->toBeFalse();
});

it('detects influencer mention above follower threshold', function () {
    $service = new AlertEvaluationService;
    $alert = createInfluencerAlert(followerThreshold: 10000);

    $mentions = [
        createAlertMention(['authorFollowerCount' => 500]),
        createAlertMention(['authorFollowerCount' => 50000]),
    ];

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 0);

    expect($result)->toBeTrue();
});

it('does not detect influencer below threshold', function () {
    $service = new AlertEvaluationService;
    $alert = createInfluencerAlert(followerThreshold: 10000);

    $mentions = [
        createAlertMention(['authorFollowerCount' => 500]),
        createAlertMention(['authorFollowerCount' => 2000]),
        createAlertMention(['authorFollowerCount' => null]),
    ];

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 0);

    expect($result)->toBeFalse();
});

it('returns false when alert cannot trigger (cooldown active)', function () {
    $service = new AlertEvaluationService;
    $now = new DateTimeImmutable;

    $alert = ListeningAlert::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Cooldown Alert',
        queryIds: ['query-1'],
        condition: AlertCondition::create(ConditionType::VolumeSpike, 10, 60),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 30,
        isActive: true,
        lastTriggeredAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    // Even with mentions exceeding threshold, should return false due to cooldown
    $mentions = array_map(fn () => createAlertMention(), range(1, 100));

    $result = $service->evaluate($alert, $mentions, previousPeriodCount: 1);

    expect($result)->toBeFalse();
});
