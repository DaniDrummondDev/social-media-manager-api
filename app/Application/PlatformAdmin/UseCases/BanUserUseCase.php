<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\BanUserInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Exceptions\UserAlreadyBannedException;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

final class BanUserUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        BanUserInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canBanUser()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $userData = $this->queryService->getUserDetail($input->userId);

        if ($userData === null) {
            throw new AdminUserNotFoundException;
        }

        if ($userData['status'] === 'suspended') {
            throw new UserAlreadyBannedException;
        }

        $this->queryService->banUser($input->userId, $input->reason);

        $this->auditService->log(
            adminId: $adminId,
            action: 'user.banned',
            resourceType: 'user',
            resourceId: $input->userId,
            context: [
                'reason' => $input->reason,
                'user_email' => $userData['email'],
                'previous_status' => $userData['status'],
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
