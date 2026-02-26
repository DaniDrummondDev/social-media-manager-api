<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Jobs;

use App\Application\AIIntelligence\DTOs\RetrieveSimilarContentInput;
use App\Application\AIIntelligence\UseCases\RetrieveSimilarContentUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class RetrieveSimilarContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $topic,
        public readonly ?string $provider = null,
        public readonly int $limit = 5,
    ) {
        $this->onQueue('content-ai');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(RetrieveSimilarContentUseCase $useCase): void
    {
        Log::info('RetrieveSimilarContentJob: Retrieving similar content via RAG.', [
            'organization_id' => $this->organizationId,
            'topic' => $this->topic,
            'provider' => $this->provider,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new RetrieveSimilarContentInput(
            organizationId: $this->organizationId,
            topic: $this->topic,
            provider: $this->provider,
            limit: $this->limit,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RetrieveSimilarContentJob: Failed.', [
            'organization_id' => $this->organizationId,
            'topic' => $this->topic,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
