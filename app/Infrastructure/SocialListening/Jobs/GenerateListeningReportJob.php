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

final class GenerateListeningReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $reportId,
        public readonly string $organizationId,
        public readonly string $userId,
    ) {
        $this->onQueue('social-listening');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(): void
    {
        Log::info('GenerateListeningReportJob: Generating report.', [
            'report_id' => $this->reportId,
            'organization_id' => $this->organizationId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        // TODO: Aggregate mentions data for the report period, compute sentiment breakdown,
        // top authors, top keywords, platform breakdown, generate file, and mark report completed.
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateListeningReportJob: Failed.', [
            'report_id' => $this->reportId,
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
