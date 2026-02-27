<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Infrastructure\Engagement\Services\ActiveCampaignConnector;
use App\Infrastructure\Engagement\Services\SalesforceConnector;
use App\Infrastructure\Engagement\Services\StubCrmConnector;

beforeEach(function () {
    $this->factory = app(CrmConnectorFactoryInterface::class);
});

it('resolves SalesforceConnector for salesforce provider', function () {
    $connector = $this->factory->make(CrmProvider::Salesforce);
    expect($connector)->toBeInstanceOf(SalesforceConnector::class);
});

it('resolves ActiveCampaignConnector for activecampaign provider', function () {
    $connector = $this->factory->make(CrmProvider::ActiveCampaign);
    expect($connector)->toBeInstanceOf(ActiveCampaignConnector::class);
});

it('resolves StubCrmConnector for hubspot provider', function () {
    $connector = $this->factory->make(CrmProvider::HubSpot);
    expect($connector)->toBeInstanceOf(StubCrmConnector::class);
});

it('resolves StubCrmConnector for rdstation provider', function () {
    $connector = $this->factory->make(CrmProvider::RdStation);
    expect($connector)->toBeInstanceOf(StubCrmConnector::class);
});

it('resolves StubCrmConnector for pipedrive provider', function () {
    $connector = $this->factory->make(CrmProvider::Pipedrive);
    expect($connector)->toBeInstanceOf(StubCrmConnector::class);
});

it('salesforce connector builds valid authorization url', function () {
    $connector = $this->factory->make(CrmProvider::Salesforce);
    $url = $connector->getAuthorizationUrl('test-state-123');

    expect($url)->toContain('login.salesforce.com/services/oauth2/authorize')
        ->and($url)->toContain('state=test-state-123')
        ->and($url)->toContain('response_type=code')
        ->and($url)->toContain('prompt=consent');
});

it('salesforce connector getConnectionStatus returns true', function () {
    $connector = $this->factory->make(CrmProvider::Salesforce);
    expect($connector->getConnectionStatus('any-token'))->toBeTrue();
});

it('activecampaign connector throws on getAuthorizationUrl', function () {
    $connector = $this->factory->make(CrmProvider::ActiveCampaign);
    $connector->getAuthorizationUrl('state');
})->throws(RuntimeException::class);

it('activecampaign connector throws on authenticate', function () {
    $connector = $this->factory->make(CrmProvider::ActiveCampaign);
    $connector->authenticate('code', 'state');
})->throws(RuntimeException::class);

it('activecampaign connector throws on refreshToken', function () {
    $connector = $this->factory->make(CrmProvider::ActiveCampaign);
    $connector->refreshToken('refresh-token');
})->throws(RuntimeException::class);

it('activecampaign connector throws on logActivity', function () {
    $connector = $this->factory->make(CrmProvider::ActiveCampaign);
    $connector->logActivity('token', 'entity-1', ['type' => 'note']);
})->throws(RuntimeException::class);

it('activecampaign connector getConnectionStatus returns true', function () {
    $connector = $this->factory->make(CrmProvider::ActiveCampaign);
    expect($connector->getConnectionStatus('any-api-key'))->toBeTrue();
});

it('activecampaign connector revokeToken is noop', function () {
    $connector = $this->factory->make(CrmProvider::ActiveCampaign);
    $connector->revokeToken('api-key');
    expect(true)->toBeTrue();
});
