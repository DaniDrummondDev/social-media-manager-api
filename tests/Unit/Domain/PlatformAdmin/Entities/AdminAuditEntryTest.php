<?php

declare(strict_types=1);

use App\Domain\PlatformAdmin\Entities\AdminAuditEntry;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates an audit entry with all fields', function () {
    $adminId = Uuid::generate();
    $entry = AdminAuditEntry::create(
        adminId: $adminId,
        action: 'organization.suspended',
        resourceType: 'organization',
        resourceId: (string) Uuid::generate(),
        context: ['reason' => 'Violation of terms'],
        ipAddress: '192.168.1.1',
        userAgent: 'Mozilla/5.0',
    );

    expect($entry->action)->toBe('organization.suspended')
        ->and($entry->resourceType)->toBe('organization')
        ->and($entry->resourceId)->not->toBeNull()
        ->and($entry->context)->toBe(['reason' => 'Violation of terms'])
        ->and($entry->ipAddress)->toBe('192.168.1.1')
        ->and($entry->userAgent)->toBe('Mozilla/5.0')
        ->and($entry->adminId->equals($adminId))->toBeTrue();
});

it('creates an audit entry without resource id', function () {
    $entry = AdminAuditEntry::create(
        adminId: Uuid::generate(),
        action: 'config.updated',
        resourceType: 'config',
        resourceId: null,
        context: ['key' => 'maintenance_mode'],
        ipAddress: '10.0.0.1',
        userAgent: null,
    );

    expect($entry->resourceId)->toBeNull()
        ->and($entry->userAgent)->toBeNull();
});

it('reconstitutes an audit entry', function () {
    $id = Uuid::generate();
    $adminId = Uuid::generate();
    $entry = AdminAuditEntry::reconstitute(
        id: $id, adminId: $adminId, action: 'user.banned',
        resourceType: 'user', resourceId: (string) Uuid::generate(),
        context: [], ipAddress: '10.0.0.1', userAgent: null,
        createdAt: new DateTimeImmutable,
    );

    expect($entry->id->equals($id))->toBeTrue()
        ->and($entry->action)->toBe('user.banned');
});
