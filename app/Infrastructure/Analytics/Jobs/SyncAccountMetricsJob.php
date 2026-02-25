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

    public function __construct()
    {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SocialAccountModel> $accounts */
        $accounts = SocialAccountModel::query()
            ->where('status', 'connected')
            ->whereNull('deleted_at')
            ->get();

        foreach ($accounts as $account) {
            SyncSingleAccountMetricsJob::dispatch($account->getAttribute('id'))
                ->onQueue('analytics');
        }
    }
}
