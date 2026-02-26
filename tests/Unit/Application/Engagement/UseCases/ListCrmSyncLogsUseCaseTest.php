<?php

declare(strict_types=1);

use App\Application\Engagement\DTOs\ListCrmSyncLogsInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\ListCrmSyncLogsUseCase;
use App\Domain\Engagement\Entities\CrmSyncLog;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;
use App\Domain\Shared\ValueObjects\Uuid;

it('returns sync logs with cursor pagination', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $logs = [
        CrmSyncLog::create(
            organizationId: $connection->organizationId,
            connectionId: $connection->id,
            direction: CrmSyncDirection::Outbound,
            entityType: CrmEntityType::Contact,
            action: 'create',
            status: CrmSyncStatus::Success,
            externalId: 'c-1',
        ),
    ];

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('findByConnectionId')->once()->andReturn($logs);

    $useCase = new ListCrmSyncLogsUseCase($connRepo, $logRepo);

    $output = $useCase->execute(new ListCrmSyncLogsInput(
        organizationId: $orgId,
        connectionId: (string) $connection->id,
        limit: 20,
    ));

    expect($output)->toHaveCount(1)
        ->and($output[0]->action)->toBe('create')
        ->and($output[0]->status)->toBe('success');
});

it('returns empty when no logs', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('findByConnectionId')->once()->andReturn([]);

    $useCase = new ListCrmSyncLogsUseCase($connRepo, $logRepo);

    $output = $useCase->execute(new ListCrmSyncLogsInput(
        organizationId: $orgId,
        connectionId: (string) $connection->id,
    ));

    expect($output)->toHaveCount(0);
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);

    $useCase = new ListCrmSyncLogsUseCase($connRepo, $logRepo);

    $useCase->execute(new ListCrmSyncLogsInput(
        organizationId: (string) Uuid::generate(),
        connectionId: (string) Uuid::generate(),
    ));
})->throws(CrmConnectionNotFoundException::class);
