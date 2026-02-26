<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\LogCrmActivityInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\LogCrmActivityUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('logs activity successfully', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('logActivity')->once()->andReturn(['id' => 'activity-1', 'data' => []]);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new LogCrmActivityUseCase($connRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new LogCrmActivityInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        connectionId: (string) $connection->id,
        entityId: 'contact-ext-1',
        activityType: 'social_engagement',
        description: 'Commented on Instagram post',
    ));

    expect($output->success)->toBeTrue()
        ->and($output->entityType)->toBe('activity')
        ->and($output->externalId)->toBe('activity-1');
});

it('returns failed result on API error', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('logActivity')->once()->andThrow(new RuntimeException('Unauthorized'));

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new LogCrmActivityUseCase($connRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new LogCrmActivityInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        connectionId: (string) $connection->id,
        entityId: 'entity-1',
        activityType: 'note',
        description: 'Test note',
    ));

    expect($output->success)->toBeFalse()
        ->and($output->errorMessage)->toBe('Unauthorized');
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new LogCrmActivityUseCase($connRepo, $logRepo, $factory, $dispatcher);

    $useCase->execute(new LogCrmActivityInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        connectionId: (string) Uuid::generate(),
        entityId: 'e-1',
        activityType: 'note',
        description: 'Test',
    ));
})->throws(CrmConnectionNotFoundException::class);
