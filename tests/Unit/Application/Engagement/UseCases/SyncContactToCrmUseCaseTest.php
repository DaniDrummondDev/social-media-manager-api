<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\SyncContactToCrmInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\SyncContactToCrmUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmFieldMapping;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates contact when none exists', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('searchContacts')->once()->andReturn([]);
    $connector->shouldReceive('createContact')->once()->andReturn(['id' => 'crm-contact-1', 'data' => []]);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->andReturn($connector);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('findByConnectionId')->once()->andReturn([]);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new SyncContactToCrmUseCase($connRepo, $mappingRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new SyncContactToCrmInput(
        organizationId: $orgId,
        userId: $userId,
        connectionId: (string) $connection->id,
        authorName: 'John Doe',
        authorExternalId: 'author-123',
    ));

    expect($output->success)->toBeTrue()
        ->and($output->action)->toBe('create')
        ->and($output->externalId)->toBe('crm-contact-1');
});

it('updates contact when already exists', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('searchContacts')->once()->andReturn([['id' => 'existing-1']]);
    $connector->shouldReceive('updateContact')->once()->andReturn(['id' => 'existing-1', 'data' => []]);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->andReturn($connector);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('findByConnectionId')->once()->andReturn([]);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new SyncContactToCrmUseCase($connRepo, $mappingRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new SyncContactToCrmInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        connectionId: (string) $connection->id,
        authorName: 'John Doe',
        authorExternalId: 'author-123',
    ));

    expect($output->success)->toBeTrue()
        ->and($output->action)->toBe('update')
        ->and($output->externalId)->toBe('existing-1');
});

it('applies field mappings with transforms', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('searchContacts')->once()->andReturn([]);
    $connector->shouldReceive('createContact')
        ->once()
        ->withArgs(function ($token, $data) {
            return $data['contact_name'] === 'JOHN DOE';
        })
        ->andReturn(['id' => 'crm-1', 'data' => []]);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->andReturn($connector);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('findByConnectionId')->once()->andReturn([
        CrmFieldMapping::create('name', 'contact_name', 'uppercase', 0),
    ]);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new SyncContactToCrmUseCase($connRepo, $mappingRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new SyncContactToCrmInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        connectionId: (string) $connection->id,
        authorName: 'John Doe',
        authorExternalId: 'author-123',
    ));

    expect($output->success)->toBeTrue();
});

it('returns failed result on API error', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->andReturn($connection);

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('searchContacts')->once()->andThrow(new RuntimeException('API error'));

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->andReturn($connector);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('findByConnectionId')->once()->andReturn([]);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new SyncContactToCrmUseCase($connRepo, $mappingRepo, $logRepo, $factory, $dispatcher);

    $output = $useCase->execute(new SyncContactToCrmInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        connectionId: (string) $connection->id,
        authorName: 'John',
        authorExternalId: 'ext-1',
    ));

    expect($output->success)->toBeFalse()
        ->and($output->errorMessage)->toBe('API error');
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new SyncContactToCrmUseCase($connRepo, $mappingRepo, $logRepo, $factory, $dispatcher);

    $useCase->execute(new SyncContactToCrmInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        connectionId: (string) Uuid::generate(),
        authorName: 'John',
        authorExternalId: 'ext-1',
    ));
})->throws(CrmConnectionNotFoundException::class);
