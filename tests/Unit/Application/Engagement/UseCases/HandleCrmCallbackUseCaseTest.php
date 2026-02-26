<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Contracts\CrmOAuthStateServiceInterface;
use App\Application\Engagement\DTOs\HandleCrmCallbackInput;
use App\Application\Engagement\Exceptions\CrmOAuthStateInvalidException;
use App\Application\Engagement\UseCases\HandleCrmCallbackUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Exceptions\CrmConnectionAlreadyExistsException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates connection from valid callback', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $stateService = Mockery::mock(CrmOAuthStateServiceInterface::class);
    $stateService->shouldReceive('validateAndConsumeState')
        ->once()
        ->andReturn([
            'organizationId' => $orgId,
            'userId' => $userId,
            'provider' => 'hubspot',
        ]);

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('authenticate')->once()->andReturn([
        'access_token' => 'access-123',
        'refresh_token' => 'refresh-456',
        'expires_at' => null,
        'account_id' => 'hub-acc-1',
        'account_name' => 'My HubSpot',
    ]);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationAndProvider')->once()->andReturn(null);
    $connRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new HandleCrmCallbackUseCase($stateService, $factory, $connRepo, $dispatcher);

    $output = $useCase->execute(new HandleCrmCallbackInput(
        code: 'auth-code-xyz',
        state: 'valid-state',
    ));

    expect($output->provider)->toBe('hubspot')
        ->and($output->accountName)->toBe('My HubSpot')
        ->and($output->status)->toBe('connected');
});

it('throws on invalid state', function () {
    $stateService = Mockery::mock(CrmOAuthStateServiceInterface::class);
    $stateService->shouldReceive('validateAndConsumeState')->once()->andReturn(null);

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new HandleCrmCallbackUseCase($stateService, $factory, $connRepo, $dispatcher);

    $useCase->execute(new HandleCrmCallbackInput(code: 'code', state: 'invalid'));
})->throws(CrmOAuthStateInvalidException::class);

it('throws when connection already exists for provider', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $stateService = Mockery::mock(CrmOAuthStateServiceInterface::class);
    $stateService->shouldReceive('validateAndConsumeState')
        ->once()
        ->andReturn([
            'organizationId' => $orgId,
            'userId' => $userId,
            'provider' => 'hubspot',
        ]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationAndProvider')->once()->andReturn(
        createReconstitutedConnection(['organizationId' => $orgId])
    );

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new HandleCrmCallbackUseCase($stateService, $factory, $connRepo, $dispatcher);

    $useCase->execute(new HandleCrmCallbackInput(code: 'code', state: 'state'));
})->throws(CrmConnectionAlreadyExistsException::class);
