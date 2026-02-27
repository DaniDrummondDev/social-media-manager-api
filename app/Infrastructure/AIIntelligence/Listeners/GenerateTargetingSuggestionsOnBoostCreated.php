<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\PaidAdvertising\Events\BoostCreated;
use App\Infrastructure\AIIntelligence\Jobs\GenerateAdTargetingSuggestionsJob;

final class GenerateTargetingSuggestionsOnBoostCreated
{
    public function handle(BoostCreated $event): void
    {
        GenerateAdTargetingSuggestionsJob::dispatch(
            organizationId: $event->organizationId,
            userId: $event->userId,
            contentId: $event->scheduledPostId,
        );
    }
}
