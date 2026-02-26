<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Jobs;

use App\Application\ContentAI\DTOs\CalculatePromptPerformanceInput;
use App\Application\ContentAI\UseCases\CalculatePromptPerformanceUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CalculatePromptPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly ?string $organizationId = null,
        public readonly ?string $generationType = null,
    ) {
        $this->onQueue('content-ai');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(CalculatePromptPerformanceUseCase $useCase): void
    {
        Log::info('CalculatePromptPerformanceJob: Recalculating prompt performance scores.', [
            'organization_id' => $this->organizationId,
            'generation_type' => $this->generationType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new CalculatePromptPerformanceInput(
            userId: 'system',
            organizationId: $this->organizationId,
            generationType: $this->generationType,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculatePromptPerformanceJob: Failed.', [
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
