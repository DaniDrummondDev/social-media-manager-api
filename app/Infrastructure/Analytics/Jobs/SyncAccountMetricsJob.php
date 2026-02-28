<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Jobs;

use App\Infrastructure\SocialAccount\Models\SocialAccountModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        SocialAccountModel::query()
            ->where('status', 'connected')
            ->whereNull('deleted_at')
            ->chunkById(200, function ($accounts): void {
                /** @var \Illuminate\Database\Eloquent\Collection<int, SocialAccountModel> $accounts */
                foreach ($accounts as $account) {
                    SyncSingleAccountMetricsJob::dispatch($account->getAttribute('id'))
                        ->onQueue('analytics');
                }
            });
    }
}
