<?php

declare(strict_types=1);

use App\Domain\PlatformAdmin\Entities\PlatformAdmin;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\ValueObjects\Uuid;

it('reconstitutes a platform admin', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(),
        userId: Uuid::generate(),
        role: PlatformRole::SuperAdmin,
        permissions: [],
        isActive: true,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect($admin->role)->toBe(PlatformRole::SuperAdmin)
        ->and($admin->isActive)->toBeTrue()
        ->and($admin->lastLoginAt)->toBeNull();
});

it('activates an admin', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(), userId: Uuid::generate(), role: PlatformRole::Admin,
        permissions: [], isActive: false, lastLoginAt: null,
        createdAt: new DateTimeImmutable, updatedAt: new DateTimeImmutable,
    );

    $activated = $admin->activate();
    expect($activated->isActive)->toBeTrue();
});

it('deactivates an admin', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(), userId: Uuid::generate(), role: PlatformRole::Admin,
        permissions: [], isActive: true, lastLoginAt: null,
        createdAt: new DateTimeImmutable, updatedAt: new DateTimeImmutable,
    );

    $deactivated = $admin->deactivate();
    expect($deactivated->isActive)->toBeFalse();
});

it('updates last login', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(), userId: Uuid::generate(), role: PlatformRole::Support,
        permissions: [], isActive: true, lastLoginAt: null,
        createdAt: new DateTimeImmutable, updatedAt: new DateTimeImmutable,
    );

    $now = new DateTimeImmutable;
    $updated = $admin->updateLastLogin($now);
    expect($updated->lastLoginAt)->not->toBeNull()
        ->and($updated->lastLoginAt->format('Y-m-d H:i:s'))->toBe($now->format('Y-m-d H:i:s'));
});
