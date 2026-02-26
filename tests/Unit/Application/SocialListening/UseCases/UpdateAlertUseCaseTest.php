<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\ListeningAlertOutput;
use App\Application\SocialListening\DTOs\UpdateAlertInput;
use App\Application\SocialListening\Exceptions\ListeningAlertNotFoundException;
use App\Application\SocialListening\UseCases\UpdateAlertUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;

beforeEach(function () {
    $this->alertRepository = Mockery::mock(ListeningAlertRepositoryInterface::class);

    $this->useCase = new UpdateAlertUseCase(
        $this->alertRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->alertId = 'e5f6a7b8-c9d0-1234-ef00-567890123456';
});

it('updates an alert successfully', function () {
    $existingAlert = ListeningAlert::reconstitute(
        id: Uuid::fromString($this->alertId),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Old Alert Name',
        queryIds: ['b1c2d3e4-f5a6-7890-bcde-f12345678901'],
        condition: AlertCondition::create(
            type: ConditionType::VolumeSpike,
            threshold: 50,
            windowMinutes: 60,
        ),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 120,
        isActive: true,
        lastTriggeredAt: null,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->alertRepository->shouldReceive('findById')->once()->andReturn($existingAlert);
    $this->alertRepository->shouldReceive('update')->once();

    $input = new UpdateAlertInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        alertId: $this->alertId,
        name: 'Updated Alert Name',
        cooldownMinutes: 240,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningAlertOutput::class)
        ->and($output->name)->toBe('Updated Alert Name')
        ->and($output->cooldownMinutes)->toBe(240);
});

it('throws when alert not found', function () {
    $this->alertRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new UpdateAlertInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        alertId: $this->alertId,
        name: 'Updated Name',
    );

    $this->useCase->execute($input);
})->throws(ListeningAlertNotFoundException::class);

it('throws when alert belongs to different organization', function () {
    $differentOrgId = 'c3d4e5f6-a7b8-9012-cdef-345678901234';

    $existingAlert = ListeningAlert::reconstitute(
        id: Uuid::fromString($this->alertId),
        organizationId: Uuid::fromString($differentOrgId),
        name: 'Alert',
        queryIds: ['b1c2d3e4-f5a6-7890-bcde-f12345678901'],
        condition: AlertCondition::create(
            type: ConditionType::VolumeSpike,
            threshold: 50,
            windowMinutes: 60,
        ),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 120,
        isActive: true,
        lastTriggeredAt: null,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->alertRepository->shouldReceive('findById')->once()->andReturn($existingAlert);

    $input = new UpdateAlertInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        alertId: $this->alertId,
        name: 'Updated Name',
    );

    $this->useCase->execute($input);
})->throws(ListeningAlertNotFoundException::class);
