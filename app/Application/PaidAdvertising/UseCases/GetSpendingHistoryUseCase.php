<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\GetSpendingHistoryInput;
use App\Application\PaidAdvertising\DTOs\SpendingHistoryOutput;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GetSpendingHistoryUseCase
{
    public function __construct(
        private readonly AdMetricSnapshotRepositoryInterface $metricsRepository,
    ) {}

    public function execute(GetSpendingHistoryInput $input): SpendingHistoryOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $history = $this->metricsRepository->getSpendingHistory(
            $organizationId,
            new DateTimeImmutable($input->from),
            new DateTimeImmutable($input->to),
        );

        return new SpendingHistoryOutput(history: $history);
    }
}
