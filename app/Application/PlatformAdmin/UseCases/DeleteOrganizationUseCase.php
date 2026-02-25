<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\DeleteOrganizationInput;
use App\Application\PlatformAdmin\Exceptions\AdminOrganizationNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

final class DeleteOrganizationUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        DeleteOrganizationInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canDeleteOrg()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $orgData = $this->queryService->getOrganizationDetail($input->organizationId);

        if ($orgData === null) {
            throw new AdminOrganizationNotFoundException;
        }

        $this->queryService->deleteOrganization($input->organizationId);

        $this->auditService->log(
            adminId: $adminId,
            action: 'organization.deleted',
            resourceType: 'organization',
            resourceId: $input->organizationId,
            context: [
                'reason' => $input->reason,
                'organization_name' => $orgData['name'],
                'members_count' => $orgData['members'] ? count($orgData['members']) : 0,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
