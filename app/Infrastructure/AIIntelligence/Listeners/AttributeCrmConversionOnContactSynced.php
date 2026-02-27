<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\Engagement\Events\CrmContactSynced;
use App\Infrastructure\AIIntelligence\Jobs\AttributeCrmConversionJob;

final class AttributeCrmConversionOnContactSynced
{
    public function handle(CrmContactSynced $event): void
    {
        AttributeCrmConversionJob::dispatch(
            organizationId: $event->organizationId,
            userId: $event->userId,
            crmConnectionId: $event->connectionId,
            contentId: $event->aggregateId,
            crmEntityType: 'contact',
            crmEntityId: $event->externalContactId,
            attributionType: 'lead_capture',
        );
    }
}
