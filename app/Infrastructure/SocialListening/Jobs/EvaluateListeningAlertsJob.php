<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Jobs;

use App\Application\SocialListening\UseCases\EvaluateAlertsUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EvaluateListeningAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct()
    {
        $this->onQueue('social-listening');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(EvaluateAlertsUseCase $useCase): void
    {
        Log::info('EvaluateListeningAlertsJob: Evaluating active alerts.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $triggeredCount = $useCase->execute();

        Log::info('EvaluateListeningAlertsJob: Completed.', [
            'triggered_count' => $triggeredCount,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EvaluateListeningAlertsJob: Failed.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
