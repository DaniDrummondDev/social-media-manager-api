<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Events\ListeningAlertTriggered;
use App\Domain\SocialListening\Exceptions\AlertCooldownActiveException;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;

function createListeningAlert(array $overrides = []): ListeningAlert
{
    return ListeningAlert::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        name: $overrides['name'] ?? 'Volume Alert',
        queryIds: $overrides['queryIds'] ?? ['query-1'],
        condition: $overrides['condition'] ?? AlertCondition::create(ConditionType::VolumeSpike, 50, 60),
        channels: $overrides['channels'] ?? [NotificationChannel::Email],
        cooldownMinutes: $overrides['cooldownMinutes'] ?? 30,
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates with default values', function () {
    $alert = createListeningAlert();

    expect($alert->isActive)->toBeTrue()
        ->and($alert->lastTriggeredAt)->toBeNull()
        ->and($alert->name)->toBe('Volume Alert')
        ->and($alert->queryIds)->toBe(['query-1'])
        ->and($alert->condition->type)->toBe(ConditionType::VolumeSpike)
        ->and($alert->condition->threshold)->toBe(50)
        ->and($alert->channels)->toBe([NotificationChannel::Email])
        ->and($alert->cooldownMinutes)->toBe(30);
});

it('activates a deactivated alert', function () {
    $alert = createListeningAlert();
    $deactivated = $alert->deactivate();
    $activated = $deactivated->activate();

    expect($activated->isActive)->toBeTrue()
        ->and($deactivated->isActive)->toBeFalse();
});

it('deactivates an active alert', function () {
    $alert = createListeningAlert();
    $deactivated = $alert->deactivate();

    expect($deactivated->isActive)->toBeFalse()
        ->and($alert->isActive)->toBeTrue();
});

it('can trigger when no previous trigger', function () {
    $alert = createListeningAlert();

    expect($alert->canTrigger())->toBeTrue();
});

it('cannot trigger during cooldown', function () {
    $orgId = Uuid::generate();
    $now = new DateTimeImmutable;

    $alert = ListeningAlert::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        name: 'Cooldown Alert',
        queryIds: ['query-1'],
        condition: AlertCondition::create(ConditionType::VolumeSpike, 50, 60),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 30,
        isActive: true,
        lastTriggeredAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($alert->canTrigger())->toBeFalse();
});

it('marks as triggered with events', function () {
    $alert = createListeningAlert();
    $triggered = $alert->markTriggered('query-1');

    expect($triggered->lastTriggeredAt)->not->toBeNull()
        ->and($triggered->domainEvents)->toHaveCount(1)
        ->and($triggered->domainEvents[0])->toBeInstanceOf(ListeningAlertTriggered::class);
});

it('throws when marking triggered during cooldown', function () {
    $orgId = Uuid::generate();
    $now = new DateTimeImmutable;

    $alert = ListeningAlert::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        name: 'Cooldown Alert',
        queryIds: ['query-1'],
        condition: AlertCondition::create(ConditionType::VolumeSpike, 50, 60),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 30,
        isActive: true,
        lastTriggeredAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    $alert->markTriggered('query-1');
})->throws(AlertCooldownActiveException::class);

it('updates details', function () {
    $alert = createListeningAlert();
    $newCondition = AlertCondition::create(ConditionType::NegativeSentimentSpike, 30, 120);

    $updated = $alert->updateDetails(
        name: 'Updated Alert',
        condition: $newCondition,
        queryIds: ['query-2', 'query-3'],
        channels: [NotificationChannel::Webhook],
        cooldownMinutes: 60,
    );

    expect($updated->name)->toBe('Updated Alert')
        ->and($updated->condition->type)->toBe(ConditionType::NegativeSentimentSpike)
        ->and($updated->condition->threshold)->toBe(30)
        ->and($updated->queryIds)->toBe(['query-2', 'query-3'])
        ->and($updated->channels)->toBe([NotificationChannel::Webhook])
        ->and($updated->cooldownMinutes)->toBe(60)
        ->and($updated->id)->toEqual($alert->id);
});

it('reconstitutes', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $lastTriggered = new DateTimeImmutable('2025-01-10 12:00:00');
    $createdAt = new DateTimeImmutable('2025-01-01 08:00:00');
    $updatedAt = new DateTimeImmutable('2025-01-10 12:00:00');
    $condition = AlertCondition::create(ConditionType::InfluencerMention, 10000, 60);

    $alert = ListeningAlert::reconstitute(
        id: $id,
        organizationId: $orgId,
        name: 'Reconstituted Alert',
        queryIds: ['q-1', 'q-2'],
        condition: $condition,
        channels: [NotificationChannel::Email, NotificationChannel::Webhook],
        cooldownMinutes: 120,
        isActive: false,
        lastTriggeredAt: $lastTriggered,
        createdAt: $createdAt,
        updatedAt: $updatedAt,
    );

    expect($alert->id)->toEqual($id)
        ->and($alert->organizationId)->toEqual($orgId)
        ->and($alert->name)->toBe('Reconstituted Alert')
        ->and($alert->queryIds)->toBe(['q-1', 'q-2'])
        ->and($alert->condition->type)->toBe(ConditionType::InfluencerMention)
        ->and($alert->channels)->toBe([NotificationChannel::Email, NotificationChannel::Webhook])
        ->and($alert->cooldownMinutes)->toBe(120)
        ->and($alert->isActive)->toBeFalse()
        ->and($alert->lastTriggeredAt)->toEqual($lastTriggered)
        ->and($alert->domainEvents)->toBeEmpty();
});
