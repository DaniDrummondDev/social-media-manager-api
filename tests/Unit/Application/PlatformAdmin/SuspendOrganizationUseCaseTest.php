<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\SuspendOrganizationInput;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\SuspendOrganizationUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Exceptions\OrganizationAlreadySuspendedException;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

it('suspends an active organization successfully', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000001';

    $queryService->shouldReceive('getOrganizationDetail')
        ->with($orgId)
        ->once()
        ->andReturn(['id' => $orgId, 'name' => 'Test Org', 'status' => 'active']);

    $queryService->shouldReceive('suspendOrganization')
        ->with($orgId, 'Violation of terms')
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'organization.suspended',
            'organization',
            $orgId,
            Mockery::on(fn (array $ctx) => $ctx['reason'] === 'Violation of terms' && $ctx['previous_status'] === 'active'),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new SuspendOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new SuspendOrganizationInput($orgId, 'Violation of terms'),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('throws OrganizationAlreadySuspendedException when organization is already suspended', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000001';

    $queryService->shouldReceive('getOrganizationDetail')
        ->with($orgId)
        ->once()
        ->andReturn(['id' => $orgId, 'name' => 'Suspended Org', 'status' => 'suspended']);

    $useCase = new SuspendOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new SuspendOrganizationInput($orgId, 'Reason'),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(OrganizationAlreadySuspendedException::class);

it('throws InsufficientAdminPrivilegeException when role is support', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000001';

    $useCase = new SuspendOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new SuspendOrganizationInput($orgId, 'Reason'),
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('throws AdminOrganizationNotFoundException when organization does not exist', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $orgId = '00000000-0000-4000-a000-000000000099';

    $queryService->shouldReceive('getOrganizationDetail')
        ->with($orgId)
        ->once()
        ->andReturn(null);

    $useCase = new SuspendOrganizationUseCase($queryService, $auditService);
    $useCase->execute(
        new SuspendOrganizationInput($orgId, 'Reason'),
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(App\Application\PlatformAdmin\Exceptions\AdminOrganizationNotFoundException::class);
