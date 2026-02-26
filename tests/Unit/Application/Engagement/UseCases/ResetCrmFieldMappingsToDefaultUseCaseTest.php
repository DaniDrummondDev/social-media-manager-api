<?php

declare(strict_types=1);

use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\ResetCrmFieldMappingsToDefaultUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmFieldMapping;
use App\Domain\Shared\ValueObjects\Uuid;

it('resets to default and dispatches event', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $defaults = [
        CrmFieldMapping::create('name', 'firstname', null, 0),
        CrmFieldMapping::create('external_id', 'hs_object_id', null, 1),
    ];

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $mappingRepo->shouldReceive('resetToDefault')->once();
    $mappingRepo->shouldReceive('findDefaultByProvider')->once()->andReturn($defaults);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new ResetCrmFieldMappingsToDefaultUseCase($connRepo, $mappingRepo, $dispatcher);

    $output = $useCase->execute($orgId, $userId, (string) $connection->id);

    expect($output)->toHaveCount(2)
        ->and($output[0]->smmField)->toBe('name');
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $mappingRepo = Mockery::mock(CrmFieldMappingRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new ResetCrmFieldMappingsToDefaultUseCase($connRepo, $mappingRepo, $dispatcher);

    $useCase->execute((string) Uuid::generate(), (string) Uuid::generate(), (string) Uuid::generate());
})->throws(CrmConnectionNotFoundException::class);
