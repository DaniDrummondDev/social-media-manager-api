<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Infrastructure\SocialAccount\Models\SocialAccountModel;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

final class CaptureCommentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('engagement');
    }

    public function handle(): void
    {
        $jobs = [];

        // Use cursor() for memory efficiency - streams results instead of loading chunks
        SocialAccountModel::query()
            ->whereNotNull('connected_at')
            ->whereNull('deleted_at')
            ->select(['id'])
            ->cursor()
            ->each(function (SocialAccountModel $account) use (&$jobs): void {
                $jobs[] = new CaptureSingleAccountCommentsJob($account->getAttribute('id'));
            });

        if (count($jobs) === 0) {
            Log::info('CaptureCommentsJob: No accounts to process.');

            return;
        }

        // Dispatch as a batch for better tracking
        Bus::batch($jobs)
            ->name('capture-comments-'.now()->format('Y-m-d-H-i'))
            ->onQueue('engagement')
            ->allowFailures()
            ->then(function (Batch $batch): void {
                Log::info('CaptureCommentsJob: Batch completed.', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->dispatch();

        Log::info('CaptureCommentsJob: Dispatched batch.', [
            'total_accounts' => count($jobs),
        ]);
    }
}
