<?php

declare(strict_types=1);

use App\Infrastructure\Engagement\Services\SalesforceConnector;

beforeEach(function () {
    $this->connector = new SalesforceConnector(config: [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'redirect_uri' => 'https://app.example.com/crm/salesforce/callback',
        'instance_url' => 'https://login.salesforce.com',
        'api_version' => 'v58.0',
        'scopes' => ['api', 'refresh_token', 'offline_access'],
    ]);
});

it('builds authorization url with correct params', function () {
    $url = $this->connector->getAuthorizationUrl('my-state-token');

    expect($url)->toContain('https://login.salesforce.com/services/oauth2/authorize')
        ->and($url)->toContain('client_id=test-client-id')
        ->and($url)->toContain('redirect_uri=' . urlencode('https://app.example.com/crm/salesforce/callback'))
        ->and($url)->toContain('state=my-state-token')
        ->and($url)->toContain('response_type=code')
        ->and($url)->toContain('scope=' . urlencode('api refresh_token offline_access'))
        ->and($url)->toContain('prompt=consent');
});

it('getConnectionStatus returns true', function () {
    expect($this->connector->getConnectionStatus('any-token'))->toBeTrue();
});

it('authenticate throws RuntimeException', function () {
    $this->connector->authenticate('code', 'state');
})->throws(RuntimeException::class);

it('refreshToken throws RuntimeException', function () {
    $this->connector->refreshToken('refresh-token');
})->throws(RuntimeException::class);

it('revokeToken throws RuntimeException', function () {
    $this->connector->revokeToken('access-token');
})->throws(RuntimeException::class);

it('createContact throws RuntimeException', function () {
    $this->connector->createContact('token', ['FirstName' => 'John']);
})->throws(RuntimeException::class);

it('updateContact throws RuntimeException', function () {
    $this->connector->updateContact('token', 'contact-1', ['Email' => 'john@test.com']);
})->throws(RuntimeException::class);

it('createDeal throws RuntimeException', function () {
    $this->connector->createDeal('token', ['Name' => 'Opportunity 1']);
})->throws(RuntimeException::class);

it('logActivity throws RuntimeException', function () {
    $this->connector->logActivity('token', 'entity-1', ['Subject' => 'Call']);
})->throws(RuntimeException::class);

it('searchContacts throws RuntimeException', function () {
    $this->connector->searchContacts('token', 'John');
})->throws(RuntimeException::class);
