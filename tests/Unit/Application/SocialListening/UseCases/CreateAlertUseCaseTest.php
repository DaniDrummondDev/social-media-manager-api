<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\CreateAlertInput;
use App\Application\SocialListening\DTOs\ListeningAlertOutput;
use App\Application\SocialListening\UseCases\CreateAlertUseCase;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;

beforeEach(function () {
    $this->alertRepository = Mockery::mock(ListeningAlertRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CreateAlertUseCase(
        $this->alertRepository,
        $this->eventDispatcher,
    );
});

it('creates an alert successfully', function () {
    $this->alertRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new CreateAlertInput(
        organizationId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        name: 'Volume Spike Alert',
        queryIds: ['b1c2d3e4-f5a6-7890-bcde-f12345678901'],
        conditionType: 'volume_spike',
        threshold: 50,
        windowMinutes: 60,
        channels: ['email', 'in_app'],
        cooldownMinutes: 120,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningAlertOutput::class)
        ->and($output->name)->toBe('Volume Spike Alert')
        ->and($output->conditionType)->toBe('volume_spike')
        ->and($output->threshold)->toBe(50)
        ->and($output->windowMinutes)->toBe(60)
        ->and($output->channels)->toBe(['email', 'in_app'])
        ->and($output->cooldownMinutes)->toBe(120)
        ->and($output->isActive)->toBeTrue()
        ->and($output->lastTriggeredAt)->toBeNull();
});
