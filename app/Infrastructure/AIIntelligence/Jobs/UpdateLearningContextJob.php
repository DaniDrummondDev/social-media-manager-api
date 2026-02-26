<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use App\Application\AIIntelligence\DTOs\UpdateLearningContextInput;
use App\Application\AIIntelligence\UseCases\UpdateLearningContextUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class UpdateLearningContextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    /**
     * @param  array<string>  $contextTypes
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly array $contextTypes = ['rag', 'style', 'audience'],
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(UpdateLearningContextUseCase $useCase): void
    {
        Log::info('UpdateLearningContextJob: Updating learning context.', [
            'organization_id' => $this->organizationId,
            'context_types' => $this->contextTypes,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new UpdateLearningContextInput(
            organizationId: $this->organizationId,
            userId: $this->userId,
            contextTypes: $this->contextTypes,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateLearningContextJob: Failed.', [
            'organization_id' => $this->organizationId,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
