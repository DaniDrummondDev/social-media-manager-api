<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\DisconnectCrmUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('disconnects and dispatches events', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('revokeToken')->once();

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new DisconnectCrmUseCase($connRepo, $factory, $dispatcher);

    $useCase->execute($orgId, $userId, (string) $connection->id);
});

it('disconnects even if revoke fails', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('revokeToken')->once()->andThrow(new RuntimeException('Network error'));

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new DisconnectCrmUseCase($connRepo, $factory, $dispatcher);

    $useCase->execute($orgId, (string) Uuid::generate(), (string) $connection->id);
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new DisconnectCrmUseCase($connRepo, $factory, $dispatcher);

    $useCase->execute((string) Uuid::generate(), (string) Uuid::generate(), (string) Uuid::generate());
})->throws(CrmConnectionNotFoundException::class);

it('throws when organization mismatch', function () {
    $connection = createReconstitutedConnection();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new DisconnectCrmUseCase($connRepo, $factory, $dispatcher);

    $useCase->execute((string) Uuid::generate(), (string) Uuid::generate(), (string) $connection->id);
})->throws(CrmConnectionNotFoundException::class);
