<?php

declare(strict_types=1);

use App\Domain\PlatformAdmin\Entities\PlatformAdmin;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PlatformAdmin\Models\PlatformAdminModel;
use App\Infrastructure\PlatformAdmin\Repositories\EloquentPlatformAdminRepository;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::table('users')->insert([
        'id' => $this->userId = (string) Uuid::generate(),
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => 'hashed',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->repo = new EloquentPlatformAdminRepository(new PlatformAdminModel);
});

it('creates and finds by user id', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(),
        userId: Uuid::fromString($this->userId),
        role: PlatformRole::SuperAdmin,
        permissions: [],
        isActive: true,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->repo->create($admin);
    $found = $this->repo->findByUserId(Uuid::fromString($this->userId));

    expect($found)->not->toBeNull()
        ->and($found)->toBeInstanceOf(PlatformAdmin::class)
        ->and($found->role)->toBe(PlatformRole::SuperAdmin)
        ->and($found->isActive)->toBeTrue()
        ->and($found->lastLoginAt)->toBeNull()
        ->and((string) $found->userId)->toBe($this->userId);
});

it('creates and finds by id', function () {
    $adminId = Uuid::generate();

    $admin = PlatformAdmin::reconstitute(
        id: $adminId,
        userId: Uuid::fromString($this->userId),
        role: PlatformRole::Admin,
        permissions: ['manage_users' => true],
        isActive: true,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->repo->create($admin);
    $found = $this->repo->findById($adminId);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $adminId)
        ->and($found->role)->toBe(PlatformRole::Admin)
        ->and($found->permissions)->toBe(['manage_users' => true]);
});

it('returns null when user id not found', function () {
    $found = $this->repo->findByUserId(Uuid::generate());

    expect($found)->toBeNull();
});

it('returns null when id not found', function () {
    $found = $this->repo->findById(Uuid::generate());

    expect($found)->toBeNull();
});

it('counts active super admins', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(),
        userId: Uuid::fromString($this->userId),
        role: PlatformRole::SuperAdmin,
        permissions: [],
        isActive: true,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->repo->create($admin);

    $count = $this->repo->countActiveSuperAdmins();
    expect($count)->toBe(1);
});

it('does not count inactive super admins', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(),
        userId: Uuid::fromString($this->userId),
        role: PlatformRole::SuperAdmin,
        permissions: [],
        isActive: false,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->repo->create($admin);

    $count = $this->repo->countActiveSuperAdmins();
    expect($count)->toBe(0);
});

it('does not count active admins in super admin count', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(),
        userId: Uuid::fromString($this->userId),
        role: PlatformRole::Admin,
        permissions: [],
        isActive: true,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->repo->create($admin);

    $count = $this->repo->countActiveSuperAdmins();
    expect($count)->toBe(0);
});

it('updates an existing admin', function () {
    $adminId = Uuid::generate();

    $admin = PlatformAdmin::reconstitute(
        id: $adminId,
        userId: Uuid::fromString($this->userId),
        role: PlatformRole::Support,
        permissions: [],
        isActive: true,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->repo->create($admin);

    $deactivated = $admin->deactivate();
    $this->repo->update($deactivated);

    $found = $this->repo->findById($adminId);
    expect($found->isActive)->toBeFalse();
});

it('finds all admins', function () {
    $admin = PlatformAdmin::reconstitute(
        id: Uuid::generate(),
        userId: Uuid::fromString($this->userId),
        role: PlatformRole::SuperAdmin,
        permissions: [],
        isActive: true,
        lastLoginAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->repo->create($admin);

    $all = $this->repo->findAll();
    expect($all)->toHaveCount(1)
        ->and($all[0])->toBeInstanceOf(PlatformAdmin::class);
});
