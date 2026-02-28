<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\RecordUsageInput;
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
        $this->usageRecordRepository->incrementOrCreate(
            Uuid::fromString($input->organizationId),
            UsageResourceType::from($input->resourceType),
            $input->amount,
            new DateTimeImmutable('first day of this month midnight'),
            new DateTimeImmutable('last day of this month 23:59:59'),
        );
    }
}
