<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\ValueObjects;

enum PlatformRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Support = 'support';

    public function canManagePlans(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canManageConfig(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canDeleteOrg(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canBanUser(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function canSuspendOrg(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function canForceVerify(): bool
    {
        return true;
    }

    public function canResetPassword(): bool
    {
        return true;
    }

    public function canViewAuditLog(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    /**
     * @return int Higher number = more privilege
     */
    public function level(): int
    {
        return match ($this) {
            self::SuperAdmin => 3,
            self::Admin => 2,
            self::Support => 1,
        };
    }

    public function isAtLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }
}
