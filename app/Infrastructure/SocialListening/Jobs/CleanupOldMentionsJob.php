<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Jobs;

use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CleanupOldMentionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly int $retentionDays = 90,
    ) {
        $this->onQueue('social-listening');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(MentionRepositoryInterface $mentionRepository): void
    {
        Log::info('CleanupOldMentionsJob: Starting cleanup of old mentions.', [
            'retention_days' => $this->retentionDays,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $before = (new DateTimeImmutable())->modify("-{$this->retentionDays} days");
        $deleted = $mentionRepository->deleteOlderThan($before);

        Log::info('CleanupOldMentionsJob: Completed.', [
            'deleted_count' => $deleted,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupOldMentionsJob: Failed.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
