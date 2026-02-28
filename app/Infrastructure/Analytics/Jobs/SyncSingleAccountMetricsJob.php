<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Jobs;

use App\Application\Analytics\DTOs\SyncAccountMetricsInput;
use App\Application\Analytics\UseCases\SyncAccountMetricsUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncSingleAccountMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly string $socialAccountId,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(SyncAccountMetricsUseCase $useCase): void
    {
        $useCase->execute(new SyncAccountMetricsInput(
            socialAccountId: $this->socialAccountId,
        ));
    }
}
