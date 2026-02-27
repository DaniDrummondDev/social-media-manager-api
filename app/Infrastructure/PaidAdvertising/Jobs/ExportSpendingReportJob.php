<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Jobs;

use App\Application\PaidAdvertising\DTOs\ExportSpendingReportInput;
use App\Application\PaidAdvertising\UseCases\ExportSpendingReportUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ExportSpendingReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** @var array<int> */
    public array $backoff = [60, 300];

    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $from,
        public readonly string $to,
        public readonly string $format,
    ) {
        $this->onQueue('low');
    }

    public function handle(ExportSpendingReportUseCase $useCase): void
    {
        $useCase->execute(new ExportSpendingReportInput(
            organizationId: $this->organizationId,
            userId: $this->userId,
            from: $this->from,
            to: $this->to,
            format: $this->format,
        ));
    }
}
