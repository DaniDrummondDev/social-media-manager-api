<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\UnbanUserInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;

final class UnbanUserUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        UnbanUserInput $input,
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

        if ($userData['status'] !== 'suspended') {
            throw new DomainException(
                'O usuário não está banido.',
                'USER_NOT_BANNED',
            );
        }

        $this->queryService->unbanUser($input->userId);

        $this->auditService->log(
            adminId: $adminId,
            action: 'user.unbanned',
            resourceType: 'user',
            resourceId: $input->userId,
            context: [
                'user_email' => $userData['email'],
                'previous_ban_reason' => $userData['ban_reason'] ?? null,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}
