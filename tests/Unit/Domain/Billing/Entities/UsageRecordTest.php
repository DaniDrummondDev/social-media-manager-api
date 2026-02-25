<?php

declare(strict_types=1);

use App\Domain\Billing\Entities\UsageRecord;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;

describe('create', function () {
    it('builds record with correct properties', function () {
        $orgId = Uuid::generate();
        $periodStart = new DateTimeImmutable('first day of this month midnight');
        $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

        $record = UsageRecord::create(
            organizationId: $orgId,
            resourceType: UsageResourceType::Publications,
            quantity: 5,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );

        expect($record->id)->not->toBeNull()
            ->and($record->organizationId->equals($orgId))->toBeTrue()
            ->and($record->resourceType)->toBe(UsageResourceType::Publications)
            ->and($record->quantity)->toBe(5)
            ->and($record->periodStart)->toBe($periodStart)
            ->and($record->periodEnd)->toBe($periodEnd)
            ->and($record->recordedAt)->not->toBeNull();
    });
});

describe('increment', function () {
    it('increases quantity by given amount', function () {
        $record = UsageRecord::create(
            organizationId: Uuid::generate(),
            resourceType: UsageResourceType::AiGenerations,
            quantity: 10,
            periodStart: new DateTimeImmutable('first day of this month midnight'),
            periodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        );

        $incremented = $record->increment(5);

        expect($incremented->quantity)->toBe(15)
            ->and($incremented->id->equals($record->id))->toBeTrue()
            ->and($incremented->resourceType)->toBe(UsageResourceType::AiGenerations);
    });

    it('increases quantity by 1 by default', function () {
        $record = UsageRecord::create(
            organizationId: Uuid::generate(),
            resourceType: UsageResourceType::Publications,
            quantity: 7,
            periodStart: new DateTimeImmutable('first day of this month midnight'),
            periodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        );

        $incremented = $record->increment();

        expect($incremented->quantity)->toBe(8);
    });
});

describe('setQuantity', function () {
    it('replaces quantity with given value', function () {
        $record = UsageRecord::create(
            organizationId: Uuid::generate(),
            resourceType: UsageResourceType::StorageBytes,
            quantity: 100,
            periodStart: new DateTimeImmutable('first day of this month midnight'),
            periodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        );

        $updated = $record->setQuantity(42);

        expect($updated->quantity)->toBe(42)
            ->and($updated->id->equals($record->id))->toBeTrue()
            ->and($updated->resourceType)->toBe(UsageResourceType::StorageBytes);
    });
});
