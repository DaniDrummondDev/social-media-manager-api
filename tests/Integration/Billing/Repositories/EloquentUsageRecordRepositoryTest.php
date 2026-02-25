<?php

declare(strict_types=1);

use App\Domain\Billing\Entities\UsageRecord;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Repositories\EloquentUsageRecordRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->orgId = (string) Str::uuid();

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'test-'.Str::random(4),
        'timezone' => 'UTC',
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('createOrUpdate inserts new record', function () {
    $repo = app(EloquentUsageRecordRepository::class);

    $periodStart = new DateTimeImmutable('first day of this month');
    $periodEnd = new DateTimeImmutable('last day of this month');

    $record = UsageRecord::create(
        organizationId: Uuid::fromString($this->orgId),
        resourceType: UsageResourceType::Publications,
        quantity: 10,
        periodStart: $periodStart,
        periodEnd: $periodEnd,
    );

    $repo->createOrUpdate($record);

    $found = $repo->findByOrganizationAndResource(
        Uuid::fromString($this->orgId),
        UsageResourceType::Publications,
        $periodStart,
    );

    expect($found)->not->toBeNull()
        ->and($found)->toBeInstanceOf(UsageRecord::class)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->resourceType)->toBe(UsageResourceType::Publications)
        ->and($found->quantity)->toBe(10);
});

it('createOrUpdate updates existing record', function () {
    $repo = app(EloquentUsageRecordRepository::class);

    $periodStart = new DateTimeImmutable('first day of this month');
    $periodEnd = new DateTimeImmutable('last day of this month');

    $record = UsageRecord::create(
        organizationId: Uuid::fromString($this->orgId),
        resourceType: UsageResourceType::AiGenerations,
        quantity: 5,
        periodStart: $periodStart,
        periodEnd: $periodEnd,
    );

    $repo->createOrUpdate($record);

    // Increment and update
    $updated = $record->increment(15);
    $repo->createOrUpdate($updated);

    $found = $repo->findByOrganizationAndResource(
        Uuid::fromString($this->orgId),
        UsageResourceType::AiGenerations,
        $periodStart,
    );

    expect($found)->not->toBeNull()
        ->and($found->quantity)->toBe(20);

    // Verify only one record exists (upsert, not duplicate)
    $count = DB::table('usage_records')
        ->where('organization_id', $this->orgId)
        ->where('resource_type', 'ai_generations')
        ->count();

    expect($count)->toBe(1);
});

it('findByOrganizationAndResource returns correct record', function () {
    $repo = app(EloquentUsageRecordRepository::class);

    $periodStart = new DateTimeImmutable('first day of this month');
    $periodEnd = new DateTimeImmutable('last day of this month');

    // Insert multiple resource types
    $publications = UsageRecord::create(
        organizationId: Uuid::fromString($this->orgId),
        resourceType: UsageResourceType::Publications,
        quantity: 25,
        periodStart: $periodStart,
        periodEnd: $periodEnd,
    );
    $repo->createOrUpdate($publications);

    $storage = UsageRecord::create(
        organizationId: Uuid::fromString($this->orgId),
        resourceType: UsageResourceType::StorageBytes,
        quantity: 500000,
        periodStart: $periodStart,
        periodEnd: $periodEnd,
    );
    $repo->createOrUpdate($storage);

    // Find specific resource type
    $found = $repo->findByOrganizationAndResource(
        Uuid::fromString($this->orgId),
        UsageResourceType::StorageBytes,
        $periodStart,
    );

    expect($found)->not->toBeNull()
        ->and($found->resourceType)->toBe(UsageResourceType::StorageBytes)
        ->and($found->quantity)->toBe(500000);
});

it('findByOrganizationAndResource returns null when not found', function () {
    $repo = app(EloquentUsageRecordRepository::class);

    $periodStart = new DateTimeImmutable('first day of this month');

    $found = $repo->findByOrganizationAndResource(
        Uuid::fromString($this->orgId),
        UsageResourceType::Members,
        $periodStart,
    );

    expect($found)->toBeNull();
});

it('findAllByOrganizationForPeriod returns all records for org in period', function () {
    $repo = app(EloquentUsageRecordRepository::class);

    $periodStart = new DateTimeImmutable('first day of this month');
    $periodEnd = new DateTimeImmutable('last day of this month');

    $resources = [
        UsageResourceType::Publications,
        UsageResourceType::AiGenerations,
        UsageResourceType::StorageBytes,
    ];

    foreach ($resources as $i => $resourceType) {
        $record = UsageRecord::create(
            organizationId: Uuid::fromString($this->orgId),
            resourceType: $resourceType,
            quantity: ($i + 1) * 10,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );
        $repo->createOrUpdate($record);
    }

    // Add a record for a different period (should not be returned)
    $otherPeriodStart = new DateTimeImmutable('first day of last month');
    $otherPeriodEnd = new DateTimeImmutable('last day of last month');
    $otherRecord = UsageRecord::create(
        organizationId: Uuid::fromString($this->orgId),
        resourceType: UsageResourceType::Campaigns,
        quantity: 99,
        periodStart: $otherPeriodStart,
        periodEnd: $otherPeriodEnd,
    );
    $repo->createOrUpdate($otherRecord);

    $records = $repo->findAllByOrganizationForPeriod(
        Uuid::fromString($this->orgId),
        $periodStart,
    );

    expect($records)->toHaveCount(3);

    $types = array_map(fn (UsageRecord $r) => $r->resourceType, $records);

    expect($types)->toContain(UsageResourceType::Publications)
        ->and($types)->toContain(UsageResourceType::AiGenerations)
        ->and($types)->toContain(UsageResourceType::StorageBytes)
        ->and($types)->not->toContain(UsageResourceType::Campaigns);
});
