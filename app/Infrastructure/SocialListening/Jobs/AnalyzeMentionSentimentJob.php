<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AnalyzeMentionSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [10, 60, 180];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $mentionId,
        public readonly string $organizationId,
        public readonly string $userId = 'system',
    ) {
        $this->onQueue('social-listening');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(\App\Application\SocialListening\UseCases\AnalyzeMentionSentimentUseCase $useCase): void
    {
        Log::info('AnalyzeMentionSentimentJob: Starting sentiment analysis.', [
            'mention_id' => $this->mentionId,
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute($this->mentionId);

        Log::info('AnalyzeMentionSentimentJob: Completed.', [
            'mention_id' => $this->mentionId,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeMentionSentimentJob: Failed.', [
            'mention_id' => $this->mentionId,
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
