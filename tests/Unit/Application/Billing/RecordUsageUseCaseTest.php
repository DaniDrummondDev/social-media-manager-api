<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\RecordUsageInput;
use App\Application\Billing\UseCases\RecordUsageUseCase;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates new record when none exists', function () {
    $orgId = Uuid::generate();

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('incrementOrCreate')
        ->once()
        ->withArgs(function (
            Uuid $receivedOrgId,
            UsageResourceType $resourceType,
            int $amount,
        ) use ($orgId) {
            return $receivedOrgId->equals($orgId)
                && $resourceType === UsageResourceType::Publications
                && $amount === 1;
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

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('incrementOrCreate')
        ->once()
        ->withArgs(function (
            Uuid $receivedOrgId,
            UsageResourceType $resourceType,
            int $amount,
        ) use ($orgId) {
            return $receivedOrgId->equals($orgId)
                && $resourceType === UsageResourceType::Publications
                && $amount === 3;
        });

    $useCase = new RecordUsageUseCase($usageRepo);

    $useCase->execute(new RecordUsageInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
        amount: 3,
    ));
});
