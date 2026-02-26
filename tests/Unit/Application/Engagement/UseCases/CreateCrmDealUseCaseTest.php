<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\CreateCrmDealInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\CreateCrmDealUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates deal successfully', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('createDeal')->once()->andReturn(['id' => 'deal-1', 'data' => []]);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new CreateCrmDealUseCase($connRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new CreateCrmDealInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        connectionId: (string) $connection->id,
        dealName: 'New Deal',
        amount: 1500.0,
    ));

    expect($output->success)->toBeTrue()
        ->and($output->entityType)->toBe('deal')
        ->and($output->action)->toBe('create')
        ->and($output->externalId)->toBe('deal-1');
});

it('returns failed result on API error', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('createDeal')->once()->andThrow(new RuntimeException('Rate limited'));

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new CreateCrmDealUseCase($connRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new CreateCrmDealInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        connectionId: (string) $connection->id,
        dealName: 'Failed Deal',
    ));

    expect($output->success)->toBeFalse()
        ->and($output->errorMessage)->toBe('Rate limited');
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new CreateCrmDealUseCase($connRepo, $logRepo, $factory, $dispatcher);

    $useCase->execute(new CreateCrmDealInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        connectionId: (string) Uuid::generate(),
        dealName: 'Deal',
    ));
})->throws(CrmConnectionNotFoundException::class);
