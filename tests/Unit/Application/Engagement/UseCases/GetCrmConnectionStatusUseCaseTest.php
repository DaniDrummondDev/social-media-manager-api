<?php

declare(strict_types=1);

use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Engagement\UseCases\GetCrmConnectionStatusUseCase;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('returns connection output', function () {
    $orgId = (string) Uuid::generate();
    $connection = createReconstitutedConnection(['organizationId' => $orgId]);

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $useCase = new GetCrmConnectionStatusUseCase($connRepo);

    $output = $useCase->execute($orgId, (string) $connection->id);

    expect($output->id)->toBe((string) $connection->id)
        ->and($output->status)->toBe('connected');
});

it('throws when not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $useCase = new GetCrmConnectionStatusUseCase($connRepo);

    $useCase->execute((string) Uuid::generate(), (string) Uuid::generate());
})->throws(CrmConnectionNotFoundException::class);

it('throws when organization mismatch', function () {
    $connection = createReconstitutedConnection();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn($connection);

    $useCase = new GetCrmConnectionStatusUseCase($connRepo);

    $useCase->execute((string) Uuid::generate(), (string) $connection->id);
})->throws(CrmConnectionNotFoundException::class);
