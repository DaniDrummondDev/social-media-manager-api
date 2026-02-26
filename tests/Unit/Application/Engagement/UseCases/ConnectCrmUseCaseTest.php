<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Contracts\CrmOAuthStateServiceInterface;
use App\Application\Engagement\DTOs\ConnectCrmInput;
use App\Application\Engagement\UseCases\ConnectCrmUseCase;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\Exceptions\CrmConnectionAlreadyExistsException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('returns authorization url and state', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationAndProvider')->once()->andReturn(null);

    $stateService = Mockery::mock(CrmOAuthStateServiceInterface::class);
    $stateService->shouldReceive('generateState')->once()->andReturn('random-state-token');

    $connector = Mockery::mock(CrmConnectorInterface::class);
    $connector->shouldReceive('getAuthorizationUrl')
        ->with('random-state-token')
        ->once()
        ->andReturn('https://hubspot.com/oauth/authorize?state=random-state-token');

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($connector);

    $useCase = new ConnectCrmUseCase($connRepo, $factory, $stateService);

    $output = $useCase->execute(new ConnectCrmInput(
        organizationId: $orgId,
        userId: $userId,
        provider: 'hubspot',
    ));

    expect($output->authorizationUrl)->toBe('https://hubspot.com/oauth/authorize?state=random-state-token')
        ->and($output->state)->toBe('random-state-token');
});

it('throws when connection already exists', function () {
    $orgId = (string) Uuid::generate();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationAndProvider')->once()->andReturn(
        createReconstitutedConnection(['organizationId' => $orgId])
    );

    $factory = Mockery::mock(CrmConnectorFactoryInterface::class);
    $stateService = Mockery::mock(CrmOAuthStateServiceInterface::class);

    $useCase = new ConnectCrmUseCase($connRepo, $factory, $stateService);

    $useCase->execute(new ConnectCrmInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        provider: 'hubspot',
    ));
})->throws(CrmConnectionAlreadyExistsException::class);
