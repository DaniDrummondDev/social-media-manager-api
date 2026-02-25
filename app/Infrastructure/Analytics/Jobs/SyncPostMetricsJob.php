<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Jobs;

use App\Application\Analytics\DTOs\SyncPostMetricsInput;
use App\Application\Analytics\UseCases\SyncPostMetricsUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncPostMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $scheduledPostId,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(SyncPostMetricsUseCase $useCase): void
    {
        $useCase->execute(new SyncPostMetricsInput(
            scheduledPostId: $this->scheduledPostId,
        ));
    }
}
