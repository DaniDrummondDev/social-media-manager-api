<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Jobs;

use App\Infrastructure\SocialAccount\Models\SocialAccountModel;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

final class SyncAccountMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        $jobs = [];

        // Collect all jobs first using cursor for memory efficiency
        SocialAccountModel::query()
            ->where('status', 'connected')
            ->whereNull('deleted_at')
            ->select(['id'])
            ->cursor()
            ->each(function (SocialAccountModel $account) use (&$jobs): void {
                $jobs[] = new SyncSingleAccountMetricsJob($account->getAttribute('id'));
            });

        if (count($jobs) === 0) {
            Log::info('SyncAccountMetricsJob: No connected accounts to sync.');

            return;
        }

        // Dispatch as a batch for better tracking and performance
        Bus::batch($jobs)
            ->name('sync-account-metrics-'.now()->format('Y-m-d-H-i'))
            ->onQueue('analytics')
            ->allowFailures()
            ->then(function (Batch $batch): void {
                Log::info('SyncAccountMetricsJob: Batch completed.', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->catch(function (Batch $batch, \Throwable $e): void {
                Log::error('SyncAccountMetricsJob: Batch had failures.', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            })
            ->dispatch();

        Log::info('SyncAccountMetricsJob: Dispatched batch.', [
            'total_accounts' => count($jobs),
        ]);
    }
}
