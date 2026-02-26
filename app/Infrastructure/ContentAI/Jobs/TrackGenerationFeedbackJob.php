<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Jobs;

use App\Application\ContentAI\DTOs\RecordGenerationFeedbackInput;
use App\Application\ContentAI\UseCases\RecordGenerationFeedbackUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TrackGenerationFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    /**
     * @param  array<string, mixed>  $originalOutput
     * @param  array<string, mixed>|null  $editedOutput
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $generationId,
        public readonly string $action,
        public readonly array $originalOutput,
        public readonly ?array $editedOutput,
        public readonly ?string $contentId,
        public readonly string $generationType,
        public readonly ?int $timeToDecisionMs,
    ) {
        $this->onQueue('content-ai');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(RecordGenerationFeedbackUseCase $useCase): void
    {
        Log::info('TrackGenerationFeedbackJob: Recording feedback.', [
            'organization_id' => $this->organizationId,
            'generation_id' => $this->generationId,
            'action' => $this->action,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new RecordGenerationFeedbackInput(
            organizationId: $this->organizationId,
            userId: $this->userId,
            generationId: $this->generationId,
            action: $this->action,
            originalOutput: $this->originalOutput,
            editedOutput: $this->editedOutput,
            contentId: $this->contentId,
            generationType: $this->generationType,
            timeToDecisionMs: $this->timeToDecisionMs,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TrackGenerationFeedbackJob: Failed.', [
            'organization_id' => $this->organizationId,
            'generation_id' => $this->generationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
