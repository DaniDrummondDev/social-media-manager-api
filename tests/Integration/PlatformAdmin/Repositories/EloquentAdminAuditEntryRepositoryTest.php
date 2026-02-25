<?php

declare(strict_types=1);

use App\Domain\PlatformAdmin\Entities\AdminAuditEntry;
use App\Domain\PlatformAdmin\Entities\PlatformAdmin;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PlatformAdmin\Models\AdminAuditEntryModel;
use App\Infrastructure\PlatformAdmin\Repositories\EloquentAdminAuditEntryRepository;
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

    $this->adminId = (string) Uuid::generate();
    DB::table('platform_admins')->insert([
        'id' => $this->adminId,
        'user_id' => $this->userId,
        'role' => PlatformRole::SuperAdmin->value,
        'permissions' => '{}',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->repo = new EloquentAdminAuditEntryRepository(new AdminAuditEntryModel);
});

it('creates and finds audit entries', function () {
    $entry = AdminAuditEntry::create(
        adminId: Uuid::fromString($this->adminId),
        action: 'organization.suspended',
        resourceType: 'organization',
        resourceId: (string) Uuid::generate(),
        context: ['reason' => 'test'],
        ipAddress: '127.0.0.1',
        userAgent: 'TestAgent',
    );

    $this->repo->create($entry);

    $result = $this->repo->findByFilters([], 20, null);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toBeInstanceOf(AdminAuditEntry::class)
        ->and($result['items'][0]->action)->toBe('organization.suspended')
        ->and($result['items'][0]->resourceType)->toBe('organization')
        ->and($result['items'][0]->context)->toBe(['reason' => 'test'])
        ->and($result['items'][0]->ipAddress)->toBe('127.0.0.1')
        ->and($result['items'][0]->userAgent)->toBe('TestAgent')
        ->and($result['has_more'])->toBeFalse()
        ->and($result['next_cursor'])->toBeNull();
});

it('filters by action', function () {
    $entry1 = AdminAuditEntry::create(
        adminId: Uuid::fromString($this->adminId),
        action: 'organization.suspended',
        resourceType: 'organization',
        resourceId: (string) Uuid::generate(),
        context: ['reason' => 'violation'],
        ipAddress: '127.0.0.1',
        userAgent: 'TestAgent',
    );

    $entry2 = AdminAuditEntry::create(
        adminId: Uuid::fromString($this->adminId),
        action: 'user.banned',
        resourceType: 'user',
        resourceId: (string) Uuid::generate(),
        context: ['reason' => 'spam'],
        ipAddress: '127.0.0.1',
        userAgent: 'TestAgent',
    );

    $this->repo->create($entry1);
    $this->repo->create($entry2);

    $result = $this->repo->findByFilters(['action' => 'user.banned'], 20, null);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0]->action)->toBe('user.banned')
        ->and($result['items'][0]->context)->toBe(['reason' => 'spam']);
});

it('filters by admin id', function () {
    $entry = AdminAuditEntry::create(
        adminId: Uuid::fromString($this->adminId),
        action: 'plan.created',
        resourceType: 'plan',
        resourceId: (string) Uuid::generate(),
        context: ['name' => 'Enterprise'],
        ipAddress: '10.0.0.1',
        userAgent: null,
    );

    $this->repo->create($entry);

    $result = $this->repo->findByFilters(['admin_id' => $this->adminId], 20, null);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0]->action)->toBe('plan.created');

    $resultOther = $this->repo->findByFilters(['admin_id' => (string) Uuid::generate()], 20, null);

    expect($resultOther['items'])->toHaveCount(0);
});

it('filters by resource type', function () {
    $entry1 = AdminAuditEntry::create(
        adminId: Uuid::fromString($this->adminId),
        action: 'organization.suspended',
        resourceType: 'organization',
        resourceId: (string) Uuid::generate(),
        context: [],
        ipAddress: '127.0.0.1',
        userAgent: null,
    );

    $entry2 = AdminAuditEntry::create(
        adminId: Uuid::fromString($this->adminId),
        action: 'user.banned',
        resourceType: 'user',
        resourceId: (string) Uuid::generate(),
        context: [],
        ipAddress: '127.0.0.1',
        userAgent: null,
    );

    $this->repo->create($entry1);
    $this->repo->create($entry2);

    $result = $this->repo->findByFilters(['resource_type' => 'organization'], 20, null);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0]->resourceType)->toBe('organization');
});

it('respects per page limit and returns has_more', function () {
    for ($i = 0; $i < 3; $i++) {
        $entry = AdminAuditEntry::create(
            adminId: Uuid::fromString($this->adminId),
            action: "action.{$i}",
            resourceType: 'test',
            resourceId: null,
            context: ['index' => $i],
            ipAddress: '127.0.0.1',
            userAgent: null,
        );
        $this->repo->create($entry);
    }

    $result = $this->repo->findByFilters([], 2, null);

    expect($result['items'])->toHaveCount(2)
        ->and($result['has_more'])->toBeTrue()
        ->and($result['next_cursor'])->not->toBeNull();
});

it('paginates with cursor', function () {
    $baseTime = new DateTimeImmutable('2025-01-01 12:00:00');

    for ($i = 0; $i < 3; $i++) {
        $timestamp = $baseTime->modify("+{$i} minutes");
        $entry = AdminAuditEntry::reconstitute(
            id: Uuid::generate(),
            adminId: Uuid::fromString($this->adminId),
            action: "action.{$i}",
            resourceType: 'test',
            resourceId: null,
            context: ['index' => $i],
            ipAddress: '127.0.0.1',
            userAgent: null,
            createdAt: $timestamp,
        );
        $this->repo->create($entry);
    }

    $firstPage = $this->repo->findByFilters([], 2, null);

    expect($firstPage['items'])->toHaveCount(2)
        ->and($firstPage['has_more'])->toBeTrue();

    $secondPage = $this->repo->findByFilters([], 2, $firstPage['next_cursor']);

    expect($secondPage['items'])->toHaveCount(1)
        ->and($secondPage['has_more'])->toBeFalse();
});

it('stores null resource id', function () {
    $entry = AdminAuditEntry::create(
        adminId: Uuid::fromString($this->adminId),
        action: 'system_config.updated',
        resourceType: 'system_config',
        resourceId: null,
        context: ['updated_configs' => []],
        ipAddress: '127.0.0.1',
        userAgent: null,
    );

    $this->repo->create($entry);

    $result = $this->repo->findByFilters([], 20, null);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0]->resourceId)->toBeNull()
        ->and($result['items'][0]->userAgent)->toBeNull();
});
