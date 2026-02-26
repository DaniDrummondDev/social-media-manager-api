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

final class GenerateCalendarSuggestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $suggestionId,
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $periodStart,
        public readonly string $periodEnd,
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(): void
    {
        Log::info('GenerateCalendarSuggestionsJob: Generating calendar suggestions.', [
            'suggestion_id' => $this->suggestionId,
            'organization_id' => $this->organizationId,
            'user_id' => $this->userId,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        // TODO: Fetch historical data, call LLM, complete suggestion entity
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateCalendarSuggestionsJob: Failed.', [
            'suggestion_id' => $this->suggestionId,
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
