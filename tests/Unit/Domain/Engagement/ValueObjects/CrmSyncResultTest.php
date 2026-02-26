<?php

declare(strict_types=1);

use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncResult;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;

it('creates success result', function () {
    $result = CrmSyncResult::success(
        direction: CrmSyncDirection::Outbound,
        entityType: CrmEntityType::Contact,
        action: 'create',
        externalId: 'crm-123',
    );

    expect($result->status)->toBe(CrmSyncStatus::Success)
        ->and($result->externalId)->toBe('crm-123')
        ->and($result->errorMessage)->toBeNull()
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->isFailed())->toBeFalse();
});

it('creates failed result', function () {
    $result = CrmSyncResult::failed(
        direction: CrmSyncDirection::Outbound,
        entityType: CrmEntityType::Deal,
        action: 'create',
        errorMessage: 'API error',
    );

    expect($result->status)->toBe(CrmSyncStatus::Failed)
        ->and($result->externalId)->toBeNull()
        ->and($result->errorMessage)->toBe('API error')
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->isFailed())->toBeTrue();
});

it('creates partial result', function () {
    $result = CrmSyncResult::partial(
        direction: CrmSyncDirection::Outbound,
        entityType: CrmEntityType::Activity,
        action: 'create',
        externalId: 'act-1',
        errorMessage: 'Some fields skipped',
    );

    expect($result->status)->toBe(CrmSyncStatus::Partial)
        ->and($result->externalId)->toBe('act-1')
        ->and($result->errorMessage)->toBe('Some fields skipped')
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->isFailed())->toBeFalse();
});

it('preserves direction and entity type', function () {
    $result = CrmSyncResult::success(
        direction: CrmSyncDirection::Inbound,
        entityType: CrmEntityType::Deal,
        action: 'deal.updated',
        externalId: 'deal-1',
    );

    expect($result->direction)->toBe(CrmSyncDirection::Inbound)
        ->and($result->entityType)->toBe(CrmEntityType::Deal)
        ->and($result->action)->toBe('deal.updated');
});
