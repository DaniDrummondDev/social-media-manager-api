<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\RecordUsageInput;
use App\Domain\Billing\Entities\UsageRecord;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class RecordUsageUseCase
{
    public function __construct(
        private readonly UsageRecordRepositoryInterface $usageRecordRepository,
    ) {}

    public function execute(RecordUsageInput $input): void
    {
        $orgId = Uuid::fromString($input->organizationId);
        $resourceType = UsageResourceType::from($input->resourceType);
        $periodStart = new DateTimeImmutable('first day of this month midnight');
        $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

        $record = $this->usageRecordRepository->findByOrganizationAndResource(
            $orgId,
            $resourceType,
            $periodStart,
        );

        if ($record !== null) {
            $record = $record->increment($input->amount);
        } else {
            $record = UsageRecord::create(
                $orgId,
                $resourceType,
                $input->amount,
                $periodStart,
                $periodEnd,
            );
        }

        $this->usageRecordRepository->createOrUpdate($record);
    }
}
