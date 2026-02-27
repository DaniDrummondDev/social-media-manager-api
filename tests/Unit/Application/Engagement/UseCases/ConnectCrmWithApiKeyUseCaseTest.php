<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\ConnectCrmWithApiKeyInput;
use App\Application\Engagement\Exceptions\CrmApiKeyInvalidException;
use App\Application\Engagement\UseCases\ConnectCrmWithApiKeyUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Exceptions\CrmConnectionAlreadyExistsException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates connection with api key and returns output', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationAndProvider')->once()->andReturn(null);
    $connRepo->shouldReceive('create')->once();

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('getConnectionStatus')
        ->with('ac-test-key-123')
        ->once()
        ->andReturn(true);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new ConnectCrmWithApiKeyUseCase($connRepo, $factory, $dispatcher);

    $output = $useCase->execute(new ConnectCrmWithApiKeyInput(
        organizationId: $orgId,
        userId: $userId,
        provider: 'activecampaign',
        apiKey: 'ac-test-key-123',
        accountName: 'My AC Account',
    ));

    expect($output->provider)->toBe('activecampaign')
        ->and($output->accountName)->toBe('My AC Account')
        ->and($output->status)->toBe('connected');
});

it('throws when api key is invalid', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationAndProvider')->once()->andReturn(null);

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('getConnectionStatus')
        ->with('invalid-key')
        ->once()
        ->andReturn(false);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new ConnectCrmWithApiKeyUseCase($connRepo, $factory, $dispatcher);

    $useCase->execute(new ConnectCrmWithApiKeyInput(
        organizationId: $orgId,
        userId: $userId,
        provider: 'activecampaign',
        apiKey: 'invalid-key',
        accountName: 'Test',
    ));
})->throws(CrmApiKeyInvalidException::class);

it('throws when connection already exists', function () {
    $orgId = (string) Uuid::generate();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationAndProvider')->once()->andReturn(
        createReconstitutedConnection([
            'organizationId' => $orgId,
            'provider' => 'activecampaign',
        ])
    );

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new ConnectCrmWithApiKeyUseCase($connRepo, $factory, $dispatcher);

    $useCase->execute(new ConnectCrmWithApiKeyInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        provider: 'activecampaign',
        apiKey: 'some-key',
        accountName: 'Test',
    ));
})->throws(CrmConnectionAlreadyExistsException::class);
