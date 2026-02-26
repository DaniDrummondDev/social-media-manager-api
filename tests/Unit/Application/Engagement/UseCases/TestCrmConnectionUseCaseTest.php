<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\TestCrmConnectionUseCase;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('returns connected status when healthy', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('getConnectionStatus')->once()->andReturn(true);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $useCase = new TestCrmConnectionUseCase($connRepo, $factory);

    $output = $useCase->execute($orgId, (string) $connection->id);

    expect($output->status)->toBe('connected');
});

it('marks error when unhealthy', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('getConnectionStatus')->once()->andReturn(false);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $useCase = new TestCrmConnectionUseCase($connRepo, $factory);

    $output = $useCase->execute($orgId, (string) $connection->id);

    expect($output->status)->toBe('error');
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);

    $useCase = new TestCrmConnectionUseCase($connRepo, $factory);

    $useCase->execute((string) Uuid::generate(), (string) Uuid::generate());
})->throws(CrmConnectionNotFoundException::class);
