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
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $scheduledPostId,
    ) {
        $this->onQueue('publishing');
    }

    public function handle(ProcessScheduledPostUseCase $useCase): void
    {
        try {
            $useCase->execute(new ProcessScheduledPostInput(
                scheduledPostId: $this->scheduledPostId,
            ));
        } catch (Throwable $e) {
            Log::error('Failed to process scheduled post', [
                'scheduled_post_id' => $this->scheduledPostId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
