<?php

declare(strict_types=1);

use App\Application\Engagement\DTOs\UpdateCrmFieldMappingsInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\UpdateCrmFieldMappingsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('updates mappings and dispatches event', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('saveForConnection')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new UpdateCrmFieldMappingsUseCase($connRepo, $mappingRepo, $dispatcher);

    $output = $useCase->execute(new UpdateCrmFieldMappingsInput(
        organizationId: $orgId,
        userId: $userId,
        connectionId: (string) $connection->id,
        mappings: [
            ['smm_field' => 'name', 'crm_field' => 'contact_name', 'transform' => 'uppercase'],
            ['smm_field' => 'email', 'crm_field' => 'email_address'],
        ],
    ));

    expect($output)->toHaveCount(2)
        ->and($output[0]->smmField)->toBe('name')
        ->and($output[0]->crmField)->toBe('contact_name')
        ->and($output[0]->transform)->toBe('uppercase')
        ->and($output[0]->position)->toBe(0)
        ->and($output[1]->position)->toBe(1);
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new UpdateCrmFieldMappingsUseCase($connRepo, $mappingRepo, $dispatcher);

    $useCase->execute(new UpdateCrmFieldMappingsInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        connectionId: (string) Uuid::generate(),
        mappings: [['smm_field' => 'name', 'crm_field' => 'name']],
    ));
})->throws(CrmConnectionNotFoundException::class);
