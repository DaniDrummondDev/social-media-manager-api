<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\RecordUsageInput;
use App\Application\Billing\UseCases\RecordUsageUseCase;
use App\Domain\Billing\Entities\UsageRecord;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates new record when none exists', function () {
    $orgId = Uuid::generate();

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('findByOrganizationAndResource')
        ->once()
        ->andReturn(null);

    $usageRepo->shouldReceive('createOrUpdate')
        ->once()
        ->withArgs(function (UsageRecord $record) use ($orgId) {
            return $record->organizationId->equals($orgId)
                && $record->resourceType === UsageResourceType::Publications
                && $record->quantity === 1;
        });

    $useCase = new RecordUsageUseCase($usageRepo);

    $useCase->execute(new RecordUsageInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
        amount: 1,
    ));
});

it('increments existing record', function () {
    $orgId = Uuid::generate();
    $periodStart = new DateTimeImmutable('first day of this month midnight');

    $existingRecord = UsageRecord::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        resourceType: UsageResourceType::Publications,
        quantity: 5,
        periodStart: $periodStart,
        periodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        recordedAt: new DateTimeImmutable,
    );

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('findByOrganizationAndResource')
        ->once()
        ->andReturn($existingRecord);

    $usageRepo->shouldReceive('createOrUpdate')
        ->once()
        ->withArgs(function (UsageRecord $record) {
            return $record->quantity === 8;
        });

    $useCase = new RecordUsageUseCase($usageRepo);

    $useCase->execute(new RecordUsageInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
        amount: 3,
    ));
});
