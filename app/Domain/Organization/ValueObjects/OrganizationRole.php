<?php

declare(strict_types=1);

namespace App\Domain\Organization\ValueObjects;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }

    public function canDeleteOrganization(): bool
    {
        return $this === self::Owner;
    }

    public function canManageContent(): bool
    {
        return true;
    }

    public function isAtLeast(self $minimumRole): bool
    {
        $hierarchy = [self::Owner->value => 2, self::Admin->value => 1, self::Member->value => 0];

        return $hierarchy[$this->value] >= $hierarchy[$minimumRole->value];
    }
}
