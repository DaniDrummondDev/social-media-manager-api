<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\RefreshCrmTokenUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('refreshes tokens successfully', function () {
    $connection = createReconstitutedConnection();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('refreshToken')->once()->andReturn([
        'access_token' => 'new-access',
        'refresh_token' => 'new-refresh',
        'expires_at' => null,
    ]);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new RefreshCrmTokenUseCase($connRepo, $factory, $dispatcher);

    $output = $useCase->execute((string) $connection->id);

    expect($output->status)->toBe('connected');
});

it('marks token expired when no refresh token', function () {
    $connection = createReconstitutedConnection(['refreshToken' => null]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new RefreshCrmTokenUseCase($connRepo, $factory, $dispatcher);

    $output = $useCase->execute((string) $connection->id);

    expect($output->status)->toBe('token_expired');
});

it('marks token expired when refresh fails', function () {
    $connection = createReconstitutedConnection();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('refreshToken')->once()->andThrow(new RuntimeException('Refresh failed'));

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new RefreshCrmTokenUseCase($connRepo, $factory, $dispatcher);

    $output = $useCase->execute((string) $connection->id);

    expect($output->status)->toBe('token_expired');
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new RefreshCrmTokenUseCase($connRepo, $factory, $dispatcher);

    $useCase->execute((string) Uuid::generate());
})->throws(CrmConnectionNotFoundException::class);
