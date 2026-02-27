<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use App\Application\AIIntelligence\DTOs\AggregateAdPerformanceInput;
use App\Application\AIIntelligence\UseCases\AggregateAdPerformanceUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AggregateAdPerformanceJob implements ShouldQueue
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
        public readonly string $adInsightType,
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(AggregateAdPerformanceUseCase $useCase): void
    {
        Log::info('AggregateAdPerformanceJob: Aggregating ad performance.', [
            'organization_id' => $this->organizationId,
            'ad_insight_type' => $this->adInsightType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new AggregateAdPerformanceInput(
            organizationId: $this->organizationId,
            userId: $this->userId,
            adInsightType: $this->adInsightType,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AggregateAdPerformanceJob: Failed.', [
            'organization_id' => $this->organizationId,
            'ad_insight_type' => $this->adInsightType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
