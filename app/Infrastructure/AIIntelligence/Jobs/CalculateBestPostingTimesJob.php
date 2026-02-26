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

final class CalculateBestPostingTimesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly ?string $provider = null,
        public readonly ?string $socialAccountId = null,
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(): void
    {
        Log::info('CalculateBestPostingTimesJob: Calculating best posting times.', [
            'organization_id' => $this->organizationId,
            'user_id' => $this->userId,
            'provider' => $this->provider,
            'social_account_id' => $this->socialAccountId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        // TODO: Analyze historical posting data, calculate engagement rates per time slot,
        // generate heatmap, top/worst slots, and create/update PostingTimeRecommendation entity.
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateBestPostingTimesJob: Failed.', [
            'organization_id' => $this->organizationId,
            'provider' => $this->provider,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
