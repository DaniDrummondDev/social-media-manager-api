<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);
});

function insertSalesforceConnection(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('crm_connections')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'provider' => 'salesforce',
        'access_token' => encryptCrmToken('sf-access-token'),
        'refresh_token' => encryptCrmToken('sf-refresh-token'),
        'token_expires_at' => now()->addHours(2)->toDateTimeString(),
        'external_account_id' => 'sf-org-1',
        'account_name' => 'My Salesforce',
        'status' => 'connected',
        'settings' => json_encode([]),
        'connected_by' => $userId,
        'last_sync_at' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'disconnected_at' => null,
    ], $overrides));

    return $id;
}

it('initiates salesforce OAuth connect 200', function () {
    $response = $this->postJson('/api/v1/crm/connect', [
        'provider' => 'salesforce',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'authorization_url',
                'state',
            ],
        ]);

    expect($response->json('data.authorization_url'))
        ->toContain('login.salesforce.com/services/oauth2/authorize');
});

it('lists salesforce connection 200', function () {
    insertSalesforceConnection($this->orgId, $this->user['id']);

    $response = $this->getJson('/api/v1/crm/connections', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.attributes.provider'))->toBe('salesforce');
});

it('shows salesforce connection 200', function () {
    $connId = insertSalesforceConnection($this->orgId, $this->user['id']);

    $response = $this->getJson("/api/v1/crm/connections/{$connId}", $this->headers);

    $response->assertStatus(200);
    expect($response->json('data.attributes.provider'))->toBe('salesforce')
        ->and($response->json('data.attributes.account_name'))->toBe('My Salesforce');
});

it('tests salesforce connection 200', function () {
    $connId = insertSalesforceConnection($this->orgId, $this->user['id']);

    $response = $this->postJson("/api/v1/crm/connections/{$connId}/test", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.status', 'connected');
});

it('deletes salesforce connection 204', function () {
    $connId = insertSalesforceConnection($this->orgId, $this->user['id']);

    $response = $this->deleteJson("/api/v1/crm/connections/{$connId}", [], $this->headers);

    $response->assertStatus(204);
    $this->assertDatabaseHas('crm_connections', [
        'id' => $connId,
        'status' => 'revoked',
    ]);
});

it('returns salesforce default field mappings with __c suffix', function () {
    $connId = insertSalesforceConnection($this->orgId, $this->user['id']);

    $response = $this->getJson("/api/v1/crm/connections/{$connId}/mappings", $this->headers);

    $response->assertStatus(200);
    $mappings = $response->json('data');

    expect(count($mappings))->toBeGreaterThanOrEqual(5);

    $crmFields = array_column($mappings, 'crm_field');
    expect($crmFields)->toContain('FirstName')
        ->and($crmFields)->toContain('Social_Media_Id__c')
        ->and($crmFields)->toContain('Email')
        ->and($crmFields)->toContain('Social_Network__c')
        ->and($crmFields)->toContain('Sentiment__c');
});

it('prevents duplicate salesforce connection', function () {
    insertSalesforceConnection($this->orgId, $this->user['id']);

    $response = $this->postJson('/api/v1/crm/connect', [
        'provider' => 'salesforce',
    ], $this->headers);

    $response->assertStatus(422);
});
