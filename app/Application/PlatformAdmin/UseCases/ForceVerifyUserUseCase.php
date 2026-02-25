<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\ForceVerifyInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

final class ForceVerifyUserUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        ForceVerifyInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canForceVerify()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $userData = $this->queryService->getUserDetail($input->userId);

        if ($userData === null) {
            throw new AdminUserNotFoundException;
        }

        $this->queryService->forceVerifyUser($input->userId);

        $this->auditService->log(
            adminId: $adminId,
            action: 'user.force_verified',
            resourceType: 'user',
            resourceId: $input->userId,
            context: [
                'user_email' => $userData['email'],
                'was_verified' => $userData['email_verified'] ?? false,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
