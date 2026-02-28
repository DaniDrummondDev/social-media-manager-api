<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Jobs;

use App\Application\Publishing\DTOs\ProcessScheduledPostInput;
use App\Application\Publishing\UseCases\ProcessScheduledPostUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        private readonly string $scheduledPostId,
    ) {
        $this->onQueue('publishing');
    }

    public function handle(ProcessScheduledPostUseCase $useCase): void
    {
        $useCase->execute(new ProcessScheduledPostInput(
            scheduledPostId: $this->scheduledPostId,
        ));
    }
}
