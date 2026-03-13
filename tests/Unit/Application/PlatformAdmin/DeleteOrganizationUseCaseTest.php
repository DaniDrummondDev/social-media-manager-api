<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\DeleteOrganizationInput;
use App\Application\PlatformAdmin\Exceptions\AdminOrganizationNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\DeleteOrganizationUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

it('deletes an organization successfully as super admin', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000020';

    $queryService->shouldReceive('getOrganizationDetail')
        ->with($orgId)
        ->once()
        ->andReturn([
            'id' => $orgId,
            'name' => 'Test Organization',
            'members' => [
                ['id' => 'member-1'],
                ['id' => 'member-2'],
            ],
        ]);

    $queryService->shouldReceive('deleteOrganization')
        ->with($orgId)
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'organization.deleted',
            'organization',
            $orgId,
            Mockery::on(fn (array $ctx) => $ctx['reason'] === 'Violação de ToS'
                && $ctx['organization_name'] === 'Test Organization'
                && $ctx['members_count'] === 2),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new DeleteOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new DeleteOrganizationInput($orgId, 'Violação de ToS'),
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('throws AdminOrganizationNotFoundException when organization does not exist', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000099';

    $queryService->shouldReceive('getOrganizationDetail')
        ->with($orgId)
        ->once()
        ->andReturn(null);

    $useCase = new DeleteOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new DeleteOrganizationInput($orgId, 'Reason'),
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(AdminOrganizationNotFoundException::class);

it('throws InsufficientAdminPrivilegeException when role is admin', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000020';

    $useCase = new DeleteOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new DeleteOrganizationInput($orgId, 'Reason'),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('throws InsufficientAdminPrivilegeException when role is support', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000020';

    $useCase = new DeleteOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new DeleteOrganizationInput($orgId, 'Reason'),
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);
