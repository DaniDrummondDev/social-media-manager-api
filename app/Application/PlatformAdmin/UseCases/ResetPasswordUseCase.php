<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\ResetPasswordInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

final class ResetPasswordUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        ResetPasswordInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canResetPassword()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $userData = $this->queryService->getUserDetail($input->userId);

        if ($userData === null) {
            throw new AdminUserNotFoundException;
        }

        // Password reset email is handled by the Identity bounded context.
        // This use case only records the admin audit trail.

        $this->auditService->log(
            adminId: $adminId,
            action: 'user.password_reset_requested',
            resourceType: 'user',
            resourceId: $input->userId,
            context: [
                'user_email' => $userData['email'],
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
