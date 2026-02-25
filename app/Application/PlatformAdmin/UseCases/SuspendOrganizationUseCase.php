<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\SuspendOrganizationInput;
use App\Application\PlatformAdmin\Exceptions\AdminOrganizationNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Exceptions\OrganizationAlreadySuspendedException;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

final class SuspendOrganizationUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        SuspendOrganizationInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canSuspendOrg()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $orgData = $this->queryService->getOrganizationDetail($input->organizationId);

        if ($orgData === null) {
            throw new AdminOrganizationNotFoundException;
        }

        if ($orgData['status'] === 'suspended') {
            throw new OrganizationAlreadySuspendedException;
        }

        $this->queryService->suspendOrganization($input->organizationId, $input->reason);

        $this->auditService->log(
            adminId: $adminId,
            action: 'organization.suspended',
            resourceType: 'organization',
            resourceId: $input->organizationId,
            context: [
                'reason' => $input->reason,
                'previous_status' => $orgData['status'],
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
