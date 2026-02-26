<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use App\Infrastructure\AIIntelligence\Models\OrgStyleProfileModel;
use App\Infrastructure\AIIntelligence\Models\PredictionValidationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CleanupExpiredLearningDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct()
    {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(): void
    {
        Log::info('CleanupExpiredLearningDataJob: Cleaning up expired learning data.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        // Remove expired style profiles (TTL 14 days)
        $expiredProfiles = OrgStyleProfileModel::where('expires_at', '<', now())->delete();
        Log::info("CleanupExpiredLearningDataJob: Deleted {$expiredProfiles} expired style profiles.");

        // Remove prediction validations older than 6 months
        $cutoff = now()->subMonths(6);
        $oldValidations = PredictionValidationModel::where('created_at', '<', $cutoff)->delete();
        Log::info("CleanupExpiredLearningDataJob: Deleted {$oldValidations} old prediction validations.");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupExpiredLearningDataJob: Failed.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
