<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\ListAlertsInput;
use App\Application\SocialListening\DTOs\ListeningAlertOutput;
use App\Application\SocialListening\UseCases\ListAlertsUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;

beforeEach(function () {
    $this->alertRepository = Mockery::mock(ListeningAlertRepositoryInterface::class);

    $this->useCase = new ListAlertsUseCase(
        $this->alertRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('lists alerts successfully', function () {
    $alert = ListeningAlert::reconstitute(
        id: Uuid::fromString('e5f6a7b8-c9d0-1234-ef00-567890123456'),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Volume Spike Alert',
        queryIds: ['b1c2d3e4-f5a6-7890-bcde-f12345678901'],
        condition: AlertCondition::create(
            type: ConditionType::VolumeSpike,
            threshold: 50,
            windowMinutes: 60,
        ),
        channels: [NotificationChannel::Email, NotificationChannel::InApp],
        cooldownMinutes: 120,
        isActive: true,
        lastTriggeredAt: null,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->alertRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [$alert],
            'next_cursor' => null,
        ]);

    $input = new ListAlertsInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result)->toBeArray()
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toBeInstanceOf(ListeningAlertOutput::class)
        ->and($result['items'][0]->name)->toBe('Volume Spike Alert')
        ->and($result['items'][0]->isActive)->toBeTrue()
        ->and($result['next_cursor'])->toBeNull();
});

it('returns empty list when no alerts exist', function () {
    $this->alertRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [],
            'next_cursor' => null,
        ]);

    $input = new ListAlertsInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toBeEmpty()
        ->and($result['next_cursor'])->toBeNull();
});
