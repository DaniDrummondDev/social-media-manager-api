<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class UpdateAIGenerationContextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $contextType,
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(): void
    {
        Log::info('UpdateAIGenerationContextJob: Starting.', [
            'organization_id' => $this->organizationId,
            'context_type' => $this->contextType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        // TODO: Compact latest insights into ai_generation_context for prompt injection
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateAIGenerationContextJob: Failed.', [
            'organization_id' => $this->organizationId,
            'context_type' => $this->contextType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
