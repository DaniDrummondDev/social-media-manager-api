<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\ValueObjects\CrmProvider;

final class CrmConnectorFactory implements CrmConnectorFactoryInterface
{
    public function make(CrmProvider $provider): CrmConnectorInterface
    {
        return match ($provider) {
            CrmProvider::Salesforce => new SalesforceConnector(
                config: config('crm.salesforce', []),
            ),
            CrmProvider::ActiveCampaign => new ActiveCampaignConnector(
                config: config('crm.activecampaign', []),
            ),
            CrmProvider::HubSpot,
            CrmProvider::RdStation,
            CrmProvider::Pipedrive => new StubCrmConnector($provider->value),
        };
    }
}
