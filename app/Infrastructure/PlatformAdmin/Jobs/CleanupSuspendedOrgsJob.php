<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class CleanupSuspendedOrgsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('admin');
    }

    public function handle(): void
    {
        DB::table('organizations')
            ->where('status', 'suspended')
            ->where('suspended_at', '<', now()->subDays(30))
            ->update([
                'status' => 'deleted',
                'deleted_at' => now(),
            ]);
    }
}
