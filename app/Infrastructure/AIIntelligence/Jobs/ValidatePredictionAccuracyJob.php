<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use App\Application\AIIntelligence\DTOs\ValidatePredictionInput;
use App\Application\AIIntelligence\UseCases\ValidatePredictionUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ValidatePredictionAccuracyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    /**
     * @param  array<string, mixed>  $metricsSnapshot
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $predictionId,
        public readonly string $contentId,
        public readonly string $provider,
        public readonly float $actualEngagementRate,
        public readonly array $metricsSnapshot,
        public readonly string $metricsCapturedAt,
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(ValidatePredictionUseCase $useCase): void
    {
        Log::info('ValidatePredictionAccuracyJob: Validating prediction.', [
            'organization_id' => $this->organizationId,
            'prediction_id' => $this->predictionId,
            'content_id' => $this->contentId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new ValidatePredictionInput(
            organizationId: $this->organizationId,
            userId: $this->userId,
            predictionId: $this->predictionId,
            contentId: $this->contentId,
            provider: $this->provider,
            actualEngagementRate: $this->actualEngagementRate,
            metricsSnapshot: $this->metricsSnapshot,
            metricsCapturedAt: $this->metricsCapturedAt,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ValidatePredictionAccuracyJob: Failed.', [
            'organization_id' => $this->organizationId,
            'prediction_id' => $this->predictionId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
