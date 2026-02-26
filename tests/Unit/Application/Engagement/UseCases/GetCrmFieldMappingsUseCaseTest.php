<?php

declare(strict_types=1);

use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\GetCrmFieldMappingsUseCase;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmFieldMapping;
use App\Domain\Shared\ValueObjects\Uuid;

it('returns custom mappings when they exist', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $customMappings = [
        CrmFieldMapping::create('name', 'full_name', null, 0),
        CrmFieldMapping::create('email', 'email_address', null, 1),
    ];

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('findByConnectionId')->once()->andReturn($customMappings);

    $useCase = new GetCrmFieldMappingsUseCase($connRepo, $mappingRepo);

    $output = $useCase->execute($orgId, (string) $connection->id);

    expect($output)->toHaveCount(2)
        ->and($output[0]->smmField)->toBe('name')
        ->and($output[0]->crmField)->toBe('full_name');
});

it('falls back to defaults when no custom mappings', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $defaults = [
        CrmFieldMapping::create('name', 'firstname', null, 0),
    ];

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('findByConnectionId')->once()->andReturn([]);
    $mappingRepo->shouldReceive('findDefaultByProvider')->once()->andReturn($defaults);

    $useCase = new GetCrmFieldMappingsUseCase($connRepo, $mappingRepo);

    $output = $useCase->execute($orgId, (string) $connection->id);

    expect($output)->toHaveCount(1)
        ->and($output[0]->smmField)->toBe('name');
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);

    $useCase = new GetCrmFieldMappingsUseCase($connRepo, $mappingRepo);

    $useCase->execute((string) Uuid::generate(), (string) Uuid::generate());
})->throws(CrmConnectionNotFoundException::class);
