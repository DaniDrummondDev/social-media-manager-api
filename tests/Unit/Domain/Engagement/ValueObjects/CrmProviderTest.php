<?php

declare(strict_types=1);

use App\Domain\Engagement\ValueObjects\CrmProvider;

it('has correct labels', function () {
    expect(CrmProvider::HubSpot->label())->toBe('HubSpot')
        ->and(CrmProvider::RdStation->label())->toBe('RD Station')
        ->and(CrmProvider::Pipedrive->label())->toBe('Pipedrive')
        ->and(CrmProvider::Salesforce->label())->toBe('Salesforce')
        ->and(CrmProvider::ActiveCampaign->label())->toBe('ActiveCampaign');
});

it('reports deal support correctly', function () {
    expect(CrmProvider::HubSpot->supportsDeals())->toBeTrue()
        ->and(CrmProvider::Pipedrive->supportsDeals())->toBeTrue()
        ->and(CrmProvider::Salesforce->supportsDeals())->toBeTrue()
        ->and(CrmProvider::ActiveCampaign->supportsDeals())->toBeTrue()
        ->and(CrmProvider::RdStation->supportsDeals())->toBeFalse();
});

it('reports activity support correctly', function () {
    expect(CrmProvider::HubSpot->supportsActivities())->toBeTrue()
        ->and(CrmProvider::Pipedrive->supportsActivities())->toBeTrue()
        ->and(CrmProvider::Salesforce->supportsActivities())->toBeTrue()
        ->and(CrmProvider::RdStation->supportsActivities())->toBeFalse()
        ->and(CrmProvider::ActiveCampaign->supportsActivities())->toBeFalse();
});

it('has correct string values', function () {
    expect(CrmProvider::HubSpot->value)->toBe('hubspot')
        ->and(CrmProvider::RdStation->value)->toBe('rdstation')
        ->and(CrmProvider::Pipedrive->value)->toBe('pipedrive')
        ->and(CrmProvider::Salesforce->value)->toBe('salesforce')
        ->and(CrmProvider::ActiveCampaign->value)->toBe('activecampaign');
});

it('creates from string value', function () {
    expect(CrmProvider::from('hubspot'))->toBe(CrmProvider::HubSpot)
        ->and(CrmProvider::from('rdstation'))->toBe(CrmProvider::RdStation)
        ->and(CrmProvider::from('pipedrive'))->toBe(CrmProvider::Pipedrive);
});
