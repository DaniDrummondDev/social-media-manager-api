<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\UnsuspendOrganizationInput;
use App\Application\PlatformAdmin\Exceptions\AdminOrganizationNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\UnsuspendOrganizationUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;

beforeEach(function () {
    $this->queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $this->auditService = Mockery::mock(AuditServiceInterface::class);

    $this->useCase = new UnsuspendOrganizationUseCase(
        $this->queryService,
        $this->auditService,
    );

    $this->adminId = 'admin-uuid';
    $this->ipAddress = '192.168.1.1';
    $this->userAgent = 'Mozilla/5.0';
});

it('should unsuspend organization successfully', function () {
    $input = new UnsuspendOrganizationInput(organizationId: 'org-uuid');

    $orgData = [
        'id' => 'org-uuid',
        'name' => 'Acme Corp',
        'status' => 'suspended',
        'suspension_reason' => 'Payment issues resolved',
    ];

    $this->queryService
        ->shouldReceive('getOrganizationDetail')
        ->once()
        ->with('org-uuid')
        ->andReturn($orgData);

    $this->queryService
        ->shouldReceive('unsuspendOrganization')
        ->once()
        ->with('org-uuid');

    $this->auditService
        ->shouldReceive('log')
        ->once()
        ->withArgs(fn ($adminId, $action, $resourceType, $resourceId, $context, $ipAddress, $userAgent) =>
            $adminId === $this->adminId &&
            $action === 'organization.unsuspended' &&
            $resourceType === 'organization' &&
            $resourceId === 'org-uuid' &&
            $context['previous_reason'] === 'Payment issues resolved' &&
            $ipAddress === $this->ipAddress &&
            $userAgent === $this->userAgent
        );

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});

it('should unsuspend organization with admin role', function () {
    $input = new UnsuspendOrganizationInput(organizationId: 'org-uuid');

    $orgData = [
        'id' => 'org-uuid',
        'name' => 'Test Org',
        'status' => 'suspended',
    ];

    $this->queryService
        ->shouldReceive('getOrganizationDetail')
        ->once()
        ->andReturn($orgData);

    $this->queryService
        ->shouldReceive('unsuspendOrganization')
        ->once();

    $this->auditService
        ->shouldReceive('log')
        ->once();

    $this->useCase->execute(
        $input,
        PlatformRole::Admin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});

it('should throw exception when support role tries to unsuspend organization', function () {
    $input = new UnsuspendOrganizationInput(organizationId: 'org-uuid');

    $this->useCase->execute(
        $input,
        PlatformRole::Support,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('should throw exception when organization not found', function () {
    $input = new UnsuspendOrganizationInput(organizationId: 'non-existent-uuid');

    $this->queryService
        ->shouldReceive('getOrganizationDetail')
        ->once()
        ->with('non-existent-uuid')
        ->andReturnNull();

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
})->throws(AdminOrganizationNotFoundException::class);

it('should throw exception when organization is not suspended', function () {
    $input = new UnsuspendOrganizationInput(organizationId: 'org-uuid');

    $orgData = [
        'id' => 'org-uuid',
        'name' => 'Active Org',
        'status' => 'active',
    ];

    $this->queryService
        ->shouldReceive('getOrganizationDetail')
        ->once()
        ->andReturn($orgData);

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
})->throws(DomainException::class, 'A organização não está suspensa.');

it('should handle null suspension reason in audit context', function () {
    $input = new UnsuspendOrganizationInput(organizationId: 'org-uuid');

    $orgData = [
        'id' => 'org-uuid',
        'name' => 'Test Org',
        'status' => 'suspended',
    ];

    $this->queryService
        ->shouldReceive('getOrganizationDetail')
        ->once()
        ->andReturn($orgData);

    $this->queryService
        ->shouldReceive('unsuspendOrganization')
        ->once();

    $this->auditService
        ->shouldReceive('log')
        ->once()
        ->withArgs(function ($adminId, $action, $resourceType, $resourceId, $context, $ipAddress, $userAgent) {
            return $context['previous_reason'] === null;
        });

    $this->useCase->execute(
        $input,
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );
});
