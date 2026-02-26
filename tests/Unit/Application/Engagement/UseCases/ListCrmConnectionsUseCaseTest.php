<?php

declare(strict_types=1);

use App\Application\Engagement\UseCases\ListCrmConnectionsUseCase;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('returns list of connections', function () {
    $orgId = (string) Uuid::generate();

    $connections = [
        createReconstitutedConnection(['organizationId' => $orgId]),
        createReconstitutedConnection(['organizationId' => $orgId, 'provider' => 'pipedrive']),
    ];

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationId')->once()->andReturn($connections);

    $useCase = new ListCrmConnectionsUseCase($connRepo);

    $output = $useCase->execute($orgId);

    expect($output)->toHaveCount(2)
        ->and($output[0]->organizationId)->toBe($orgId);
});

it('returns empty array when no connections', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $useCase = new ListCrmConnectionsUseCase($connRepo);

    $output = $useCase->execute((string) Uuid::generate());

    expect($output)->toHaveCount(0);
});
