<?php

declare(strict_types=1);

use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

describe('SuperAdmin', function () {
    it('has all permissions', function () {
        $role = PlatformRole::SuperAdmin;
        expect($role->canManagePlans())->toBeTrue()
            ->and($role->canManageConfig())->toBeTrue()
            ->and($role->canDeleteOrg())->toBeTrue()
            ->and($role->canBanUser())->toBeTrue()
            ->and($role->canSuspendOrg())->toBeTrue()
            ->and($role->canForceVerify())->toBeTrue()
            ->and($role->canResetPassword())->toBeTrue();
    });
});

describe('Admin', function () {
    it('can suspend and ban but not manage plans/config/delete', function () {
        $role = PlatformRole::Admin;
        expect($role->canManagePlans())->toBeFalse()
            ->and($role->canManageConfig())->toBeFalse()
            ->and($role->canDeleteOrg())->toBeFalse()
            ->and($role->canBanUser())->toBeTrue()
            ->and($role->canSuspendOrg())->toBeTrue();
    });
});

describe('Support', function () {
    it('can only verify and reset passwords', function () {
        $role = PlatformRole::Support;
        expect($role->canManagePlans())->toBeFalse()
            ->and($role->canBanUser())->toBeFalse()
            ->and($role->canSuspendOrg())->toBeFalse()
            ->and($role->canForceVerify())->toBeTrue()
            ->and($role->canResetPassword())->toBeTrue();
    });
});

describe('isAtLeast', function () {
    it('super admin is at least all roles', function () {
        expect(PlatformRole::SuperAdmin->isAtLeast(PlatformRole::Support))->toBeTrue()
            ->and(PlatformRole::SuperAdmin->isAtLeast(PlatformRole::Admin))->toBeTrue()
            ->and(PlatformRole::SuperAdmin->isAtLeast(PlatformRole::SuperAdmin))->toBeTrue();
    });

    it('support is not at least admin', function () {
        expect(PlatformRole::Support->isAtLeast(PlatformRole::Admin))->toBeFalse()
            ->and(PlatformRole::Support->isAtLeast(PlatformRole::SuperAdmin))->toBeFalse();
    });
});
