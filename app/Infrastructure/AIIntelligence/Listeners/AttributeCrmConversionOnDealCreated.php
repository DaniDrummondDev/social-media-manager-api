<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\Engagement\Events\CrmDealCreated;
use App\Infrastructure\AIIntelligence\Jobs\AttributeCrmConversionJob;

final class AttributeCrmConversionOnDealCreated
{
    public function handle(CrmDealCreated $event): void
    {
        AttributeCrmConversionJob::dispatch(
            organizationId: $event->organizationId,
            userId: $event->userId,
            crmConnectionId: $event->connectionId,
            contentId: $event->aggregateId,
            crmEntityType: 'deal',
            crmEntityId: $event->externalDealId,
            attributionType: 'deal_closed',
        );
    }
}
