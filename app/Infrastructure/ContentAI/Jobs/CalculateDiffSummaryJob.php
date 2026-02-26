<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Jobs;

use App\Application\ContentAI\DTOs\CalculateDiffSummaryInput;
use App\Application\ContentAI\UseCases\CalculateDiffSummaryUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CalculateDiffSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $feedbackId,
        public readonly string $organizationId,
    ) {
        $this->onQueue('content-ai');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(CalculateDiffSummaryUseCase $useCase): void
    {
        Log::info('CalculateDiffSummaryJob: Calculating diff summary.', [
            'feedback_id' => $this->feedbackId,
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new CalculateDiffSummaryInput(
            feedbackId: $this->feedbackId,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateDiffSummaryJob: Failed.', [
            'feedback_id' => $this->feedbackId,
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
