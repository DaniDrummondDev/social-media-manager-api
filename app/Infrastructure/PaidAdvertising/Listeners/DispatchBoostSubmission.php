<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Listeners;

use App\Domain\PaidAdvertising\Events\BoostCreated;
use App\Infrastructure\PaidAdvertising\Jobs\CreateAdBoostJob;

final class DispatchBoostSubmission
{
    public function handle(BoostCreated $event): void
    {
        CreateAdBoostJob::dispatch($event->aggregateId);
    }
}
