<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use App\Application\AIIntelligence\DTOs\GenerateStyleProfileInput;
use App\Application\AIIntelligence\UseCases\GenerateStyleProfileUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class GenerateOrgStyleProfileJob implements ShouldQueue
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
        public readonly string $generationType,
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(GenerateStyleProfileUseCase $useCase): void
    {
        Log::info('GenerateOrgStyleProfileJob: Generating style profile.', [
            'organization_id' => $this->organizationId,
            'generation_type' => $this->generationType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new GenerateStyleProfileInput(
            organizationId: $this->organizationId,
            userId: $this->userId,
            generationType: $this->generationType,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateOrgStyleProfileJob: Failed.', [
            'organization_id' => $this->organizationId,
            'generation_type' => $this->generationType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
