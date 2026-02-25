<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\UnsuspendOrganizationInput;
use App\Application\PlatformAdmin\Exceptions\AdminOrganizationNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

final class UnsuspendOrganizationUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        UnsuspendOrganizationInput $input,
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

        if ($orgData['status'] !== 'suspended') {
            throw new \App\Domain\Shared\Exceptions\DomainException(
                'A organização não está suspensa.',
                'ORGANIZATION_NOT_SUSPENDED',
            );
        }

        $this->queryService->unsuspendOrganization($input->organizationId);

        $this->auditService->log(
            adminId: $adminId,
            action: 'organization.unsuspended',
            resourceType: 'organization',
            resourceId: $input->organizationId,
            context: [
                'previous_reason' => $orgData['suspension_reason'] ?? null,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
