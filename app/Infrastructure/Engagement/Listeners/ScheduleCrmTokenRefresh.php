<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Listeners;

use App\Domain\Engagement\Events\CrmTokenExpired;
use App\Infrastructure\Engagement\Jobs\RefreshCrmTokenJob;

final class ScheduleCrmTokenRefresh
{
    public function handle(CrmTokenExpired $event): void
    {
        RefreshCrmTokenJob::dispatch($event->connectionId);
    }
}
