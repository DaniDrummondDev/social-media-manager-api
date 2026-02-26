<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(ListeningAlertRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'alert-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'alert-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates and retrieves by id', function () {
    $queryId = (string) Uuid::generate();
    $condition = AlertCondition::create(
        type: ConditionType::VolumeSpike,
        threshold: 50,
        windowMinutes: 60,
    );

    $alert = ListeningAlert::create(
        organizationId: Uuid::fromString($this->orgId),
        name: 'Volume Alert',
        queryIds: [$queryId],
        condition: $condition,
        channels: [NotificationChannel::Email, NotificationChannel::InApp],
        cooldownMinutes: 30,
        userId: $this->userId,
    );

    $this->repository->create($alert);

    $found = $this->repository->findById($alert->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $alert->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->name)->toBe('Volume Alert')
        ->and($found->queryIds)->toBe([$queryId])
        ->and($found->condition->type)->toBe(ConditionType::VolumeSpike)
        ->and($found->condition->threshold)->toBe(50)
        ->and($found->condition->windowMinutes)->toBe(60)
        ->and($found->channels)->toEqual([NotificationChannel::Email, NotificationChannel::InApp])
        ->and($found->cooldownMinutes)->toBe(30)
        ->and($found->isActive)->toBeTrue()
        ->and($found->lastTriggeredAt)->toBeNull();
});

it('returns null for non-existent id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('finds by organization id with cursor pagination', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 0; $i < 5; $i++) {
        $alert = ListeningAlert::create(
            organizationId: $orgId,
            name: "Alert {$i}",
            queryIds: [(string) Uuid::generate()],
            condition: AlertCondition::create(ConditionType::VolumeSpike, 10, 30),
            channels: [NotificationChannel::Email],
            cooldownMinutes: 60,
            userId: $this->userId,
        );
        $this->repository->create($alert);
    }

    $firstPage = $this->repository->findByOrganizationId($orgId, null, 3);

    expect($firstPage['items'])->toHaveCount(3)
        ->and($firstPage['next_cursor'])->not->toBeNull();

    $secondPage = $this->repository->findByOrganizationId($orgId, $firstPage['next_cursor'], 3);

    expect($secondPage['items'])->toHaveCount(2)
        ->and($secondPage['next_cursor'])->toBeNull();
});

it('finds all active alerts', function () {
    $orgId = Uuid::fromString($this->orgId);

    $activeAlert = ListeningAlert::create(
        organizationId: $orgId,
        name: 'Active Alert',
        queryIds: [(string) Uuid::generate()],
        condition: AlertCondition::create(ConditionType::VolumeSpike, 10, 30),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 60,
        userId: $this->userId,
    );
    $this->repository->create($activeAlert);

    $inactiveAlert = ListeningAlert::create(
        organizationId: $orgId,
        name: 'Inactive Alert',
        queryIds: [(string) Uuid::generate()],
        condition: AlertCondition::create(ConditionType::NegativeSentimentSpike, 5, 15),
        channels: [NotificationChannel::Webhook],
        cooldownMinutes: 120,
        userId: $this->userId,
    );
    $this->repository->create($inactiveAlert);

    $deactivated = $inactiveAlert->deactivate();
    $this->repository->update($deactivated);

    $allActive = $this->repository->findAllActive();

    expect($allActive)->toHaveCount(1)
        ->and((string) $allActive[0]->id)->toBe((string) $activeAlert->id);
});

it('updates an alert', function () {
    $orgId = Uuid::fromString($this->orgId);

    $alert = ListeningAlert::create(
        organizationId: $orgId,
        name: 'Original Alert',
        queryIds: [(string) Uuid::generate()],
        condition: AlertCondition::create(ConditionType::VolumeSpike, 10, 30),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 60,
        userId: $this->userId,
    );
    $this->repository->create($alert);

    $newCondition = AlertCondition::create(ConditionType::NegativeSentimentSpike, 20, 60);
    $updated = $alert->updateDetails(
        name: 'Updated Alert',
        condition: $newCondition,
        queryIds: null,
        channels: [NotificationChannel::Email, NotificationChannel::Webhook],
        cooldownMinutes: 120,
    );
    $this->repository->update($updated);

    $found = $this->repository->findById($alert->id);

    expect($found->name)->toBe('Updated Alert')
        ->and($found->condition->type)->toBe(ConditionType::NegativeSentimentSpike)
        ->and($found->condition->threshold)->toBe(20)
        ->and($found->cooldownMinutes)->toBe(120)
        ->and($found->channels)->toEqual([NotificationChannel::Email, NotificationChannel::Webhook]);
});

it('deletes an alert', function () {
    $alert = ListeningAlert::create(
        organizationId: Uuid::fromString($this->orgId),
        name: 'To Delete',
        queryIds: [(string) Uuid::generate()],
        condition: AlertCondition::create(ConditionType::KeywordDetected, 1, 10),
        channels: [NotificationChannel::InApp],
        cooldownMinutes: 30,
        userId: $this->userId,
    );

    $this->repository->create($alert);

    $this->repository->delete($alert->id);

    expect($this->repository->findById($alert->id))->toBeNull();
});
