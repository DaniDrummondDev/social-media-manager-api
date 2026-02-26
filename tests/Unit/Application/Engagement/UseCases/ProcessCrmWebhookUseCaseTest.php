<?php

declare(strict_types=1);

use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\ProcessCrmWebhookUseCase;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('processes contact webhook and creates log', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once()->withArgs(function ($log) {
        return $log->direction->value === 'inbound'
            && $log->entityType->value === 'contact'
            && $log->action === 'contact.created'
            && $log->status->value === 'success';
    });

    $useCase = new ProcessCrmWebhookUseCase($connRepo, $logRepo);

    $useCase->execute($orgId, (string) $connection->id, 'contact.created', ['id' => 'c-1']);
});

it('processes deal webhook with correct entity type', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once()->withArgs(function ($log) {
        return $log->entityType->value === 'deal';
    });

    $useCase = new ProcessCrmWebhookUseCase($connRepo, $logRepo);

    $useCase->execute($orgId, (string) $connection->id, 'deal.updated', ['id' => 'd-1']);
});

it('defaults to activity entity type for unknown events', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);
    $connRepo->shouldReceive('update')->once();

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);
    $logRepo->shouldReceive('create')->once()->withArgs(function ($log) {
        return $log->entityType->value === 'activity';
    });

    $useCase = new ProcessCrmWebhookUseCase($connRepo, $logRepo);

    $useCase->execute($orgId, (string) $connection->id, 'note.created', []);
});

it('throws when connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $logRepo = Mockery::mock(CrmSyncLogRepositoryInterface::class);

    $useCase = new ProcessCrmWebhookUseCase($connRepo, $logRepo);

    $useCase->execute((string) Uuid::generate(), (string) Uuid::generate(), 'contact.created', []);
})->throws(CrmConnectionNotFoundException::class);
