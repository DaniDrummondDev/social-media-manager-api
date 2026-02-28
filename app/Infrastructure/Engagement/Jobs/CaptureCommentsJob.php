<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Infrastructure\SocialAccount\Models\SocialAccountModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        SocialAccountModel::query()
            ->whereNotNull('connected_at')
            ->whereNull('deleted_at')
            ->chunkById(200, function ($accounts): void {
                /** @var SocialAccountModel $account */
                foreach ($accounts as $account) {
                    CaptureSingleAccountCommentsJob::dispatch($account->getAttribute('id'));
                }
            });
    }
}
