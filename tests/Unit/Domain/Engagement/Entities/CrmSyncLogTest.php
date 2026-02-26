<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\CrmSyncLog;
use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates a sync log with all fields', function () {
    $orgId = Uuid::generate();
    $connId = Uuid::generate();

    $log = CrmSyncLog::create(
        organizationId: $orgId,
        connectionId: $connId,
        direction: CrmSyncDirection::Outbound,
        entityType: CrmEntityType::Contact,
        action: 'create',
        status: CrmSyncStatus::Success,
        externalId: 'crm-contact-123',
        payload: ['name' => 'John'],
    );

    expect($log->id)->toBeInstanceOf(Uuid::class)
        ->and($log->organizationId)->toEqual($orgId)
        ->and($log->connectionId)->toEqual($connId)
        ->and($log->direction)->toBe(CrmSyncDirection::Outbound)
        ->and($log->entityType)->toBe(CrmEntityType::Contact)
        ->and($log->action)->toBe('create')
        ->and($log->status)->toBe(CrmSyncStatus::Success)
        ->and($log->externalId)->toBe('crm-contact-123')
        ->and($log->errorMessage)->toBeNull()
        ->and($log->payload)->toBe(['name' => 'John']);
});

it('creates a failed sync log', function () {
    $log = CrmSyncLog::create(
        organizationId: Uuid::generate(),
        connectionId: Uuid::generate(),
        direction: CrmSyncDirection::Outbound,
        entityType: CrmEntityType::Deal,
        action: 'create',
        status: CrmSyncStatus::Failed,
        errorMessage: 'API rate limit exceeded',
    );

    expect($log->status)->toBe(CrmSyncStatus::Failed)
        ->and($log->errorMessage)->toBe('API rate limit exceeded')
        ->and($log->externalId)->toBeNull()
        ->and($log->isSuccess())->toBeFalse();
});

it('marks as failed preserving immutability', function () {
    $log = CrmSyncLog::create(
        organizationId: Uuid::generate(),
        connectionId: Uuid::generate(),
        direction: CrmSyncDirection::Outbound,
        entityType: CrmEntityType::Contact,
        action: 'create',
        status: CrmSyncStatus::Success,
        externalId: 'crm-1',
    );

    $failed = $log->markFailed('Connection timeout');

    expect($log->status)->toBe(CrmSyncStatus::Success)
        ->and($failed->status)->toBe(CrmSyncStatus::Failed)
        ->and($failed->errorMessage)->toBe('Connection timeout')
        ->and($failed->externalId)->toBe('crm-1')
        ->and($failed->action)->toBe('create');
});

it('reports isSuccess correctly', function () {
    $success = CrmSyncLog::create(
        organizationId: Uuid::generate(),
        connectionId: Uuid::generate(),
        direction: CrmSyncDirection::Inbound,
        entityType: CrmEntityType::Activity,
        action: 'contact.created',
        status: CrmSyncStatus::Success,
    );

    $failed = CrmSyncLog::create(
        organizationId: Uuid::generate(),
        connectionId: Uuid::generate(),
        direction: CrmSyncDirection::Outbound,
        entityType: CrmEntityType::Contact,
        action: 'sync',
        status: CrmSyncStatus::Failed,
        errorMessage: 'error',
    );

    expect($success->isSuccess())->toBeTrue()
        ->and($failed->isSuccess())->toBeFalse();
});

it('reconstitutes from stored data', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $connId = Uuid::generate();
    $now = new DateTimeImmutable;

    $log = CrmSyncLog::reconstitute(
        id: $id,
        organizationId: $orgId,
        connectionId: $connId,
        direction: CrmSyncDirection::Inbound,
        entityType: CrmEntityType::Deal,
        action: 'deal.updated',
        status: CrmSyncStatus::Success,
        externalId: 'deal-456',
        errorMessage: null,
        payload: ['stage' => 'won'],
        createdAt: $now,
    );

    expect($log->id)->toEqual($id)
        ->and($log->direction)->toBe(CrmSyncDirection::Inbound)
        ->and($log->payload)->toBe(['stage' => 'won'])
        ->and($log->createdAt)->toBe($now);
});
