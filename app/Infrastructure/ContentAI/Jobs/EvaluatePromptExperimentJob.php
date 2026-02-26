<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Jobs;

use App\Application\ContentAI\DTOs\EvaluateExperimentInput;
use App\Application\ContentAI\UseCases\EvaluateExperimentUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EvaluatePromptExperimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $experimentId,
    ) {
        $this->onQueue('content-ai');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(EvaluateExperimentUseCase $useCase): void
    {
        Log::info('EvaluatePromptExperimentJob: Evaluating A/B experiment.', [
            'organization_id' => $this->organizationId,
            'experiment_id' => $this->experimentId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new EvaluateExperimentInput(
            organizationId: $this->organizationId,
            userId: 'system',
            experimentId: $this->experimentId,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EvaluatePromptExperimentJob: Failed.', [
            'organization_id' => $this->organizationId,
            'experiment_id' => $this->experimentId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
