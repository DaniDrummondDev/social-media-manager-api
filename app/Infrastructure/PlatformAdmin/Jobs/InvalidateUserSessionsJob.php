<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class InvalidateUserSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $userId,
    ) {
        $this->onQueue('admin');
    }

    public function handle(): void
    {
        Log::info("Invalidating sessions for user: {$this->userId}");

        // Token blacklisting would be implemented when Redis session tracking is added
    }
}
