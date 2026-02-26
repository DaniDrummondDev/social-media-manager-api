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

final class RunBrandSafetyCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $checkId,
        public readonly string $organizationId,
        public readonly string $contentId,
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(): void
    {
        Log::info('RunBrandSafetyCheckJob: Running brand safety check.', [
            'check_id' => $this->checkId,
            'organization_id' => $this->organizationId,
            'content_id' => $this->contentId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        // TODO: Fetch content text, load active BrandSafetyRules for the org,
        // evaluate rules via BrandSafetyRule::matches(), call BrandSafetyAnalyzerInterface::analyze(),
        // then call BrandSafetyCheck::complete() with results and persist via repository.
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RunBrandSafetyCheckJob: Failed.', [
            'check_id' => $this->checkId,
            'organization_id' => $this->organizationId,
            'content_id' => $this->contentId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
