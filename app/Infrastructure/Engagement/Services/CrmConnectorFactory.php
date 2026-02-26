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
            CrmProvider::HubSpot,
            CrmProvider::RdStation,
            CrmProvider::Pipedrive,
            CrmProvider::Salesforce,
            CrmProvider::ActiveCampaign => new StubCrmConnector($provider->value),
        };
    }
}
