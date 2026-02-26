<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum CrmProvider: string
{
    case HubSpot = 'hubspot';
    case RdStation = 'rdstation';
    case Pipedrive = 'pipedrive';
    case Salesforce = 'salesforce';
    case ActiveCampaign = 'activecampaign';

    public function label(): string
    {
        return match ($this) {
            self::HubSpot => 'HubSpot',
            self::RdStation => 'RD Station',
            self::Pipedrive => 'Pipedrive',
            self::Salesforce => 'Salesforce',
            self::ActiveCampaign => 'ActiveCampaign',
        };
    }

    public function supportsDeals(): bool
    {
        return match ($this) {
            self::HubSpot, self::Pipedrive, self::Salesforce, self::ActiveCampaign => true,
            self::RdStation => false,
        };
    }

    public function supportsActivities(): bool
    {
        return match ($this) {
            self::HubSpot, self::Pipedrive, self::Salesforce => true,
            self::RdStation, self::ActiveCampaign => false,
        };
    }
}
