<?php

declare(strict_types=1);

use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->seed(PlanSeeder::class);

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // CRM requires Professional plan (crm_native = true)
    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'plan_id' => PlanSeeder::PROFESSIONAL_PLAN_ID,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->startOfMonth()->toDateTimeString(),
        'current_period_end' => now()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

function insertActiveCampaignConnection(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('crm_connections')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'provider' => 'activecampaign',
        'access_token' => encryptCrmToken('ac-api-key-12345'),
        'refresh_token' => null,
        'token_expires_at' => null,
        'external_account_id' => 'activecampaign',
        'account_name' => 'My ActiveCampaign',
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

it('connects activecampaign with api key 201', function () {
    $response = $this->postJson('/api/v1/crm/connect-api-key', [
        'provider' => 'activecampaign',
        'api_key' => 'ac-test-api-key-abc123',
        'account_name' => 'My AC Account',
    ], $this->headers);

    $response->assertStatus(201);
    expect($response->json('data.attributes.provider'))->toBe('activecampaign')
        ->and($response->json('data.attributes.account_name'))->toBe('My AC Account')
        ->and($response->json('data.attributes.status'))->toBe('connected');
});

it('validates provider on connect-api-key', function () {
    $response = $this->postJson('/api/v1/crm/connect-api-key', [
        'provider' => 'hubspot',
        'api_key' => 'key',
        'account_name' => 'Test',
    ], $this->headers);

    $response->assertStatus(422);
});

it('validates api_key is required on connect-api-key', function () {
    $response = $this->postJson('/api/v1/crm/connect-api-key', [
        'provider' => 'activecampaign',
        'account_name' => 'Test',
    ], $this->headers);

    $response->assertStatus(422);
});

it('validates account_name is required on connect-api-key', function () {
    $response = $this->postJson('/api/v1/crm/connect-api-key', [
        'provider' => 'activecampaign',
        'api_key' => 'key',
    ], $this->headers);

    $response->assertStatus(422);
});

it('lists activecampaign connection 200', function () {
    insertActiveCampaignConnection($this->orgId, $this->user['id']);

    $response = $this->getJson('/api/v1/crm/connections', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.attributes.provider'))->toBe('activecampaign');
});

it('shows activecampaign connection 200', function () {
    $connId = insertActiveCampaignConnection($this->orgId, $this->user['id']);

    $response = $this->getJson("/api/v1/crm/connections/{$connId}", $this->headers);

    $response->assertStatus(200);
    expect($response->json('data.attributes.provider'))->toBe('activecampaign')
        ->and($response->json('data.attributes.account_name'))->toBe('My ActiveCampaign');
});

it('tests activecampaign connection 200', function () {
    $connId = insertActiveCampaignConnection($this->orgId, $this->user['id']);

    $response = $this->postJson("/api/v1/crm/connections/{$connId}/test", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.status', 'connected');
});

it('deletes activecampaign connection 204', function () {
    $connId = insertActiveCampaignConnection($this->orgId, $this->user['id']);

    $response = $this->deleteJson("/api/v1/crm/connections/{$connId}", [], $this->headers);

    $response->assertStatus(204);
    $this->assertDatabaseHas('crm_connections', [
        'id' => $connId,
        'status' => 'revoked',
    ]);
});

it('returns activecampaign default field mappings with fieldValues', function () {
    $connId = insertActiveCampaignConnection($this->orgId, $this->user['id']);

    $response = $this->getJson("/api/v1/crm/connections/{$connId}/mappings", $this->headers);

    $response->assertStatus(200);
    $mappings = $response->json('data');

    expect(count($mappings))->toBeGreaterThanOrEqual(5);

    $crmFields = array_column($mappings, 'crm_field');
    expect($crmFields)->toContain('firstName')
        ->and($crmFields)->toContain('fieldValues.social_id')
        ->and($crmFields)->toContain('email')
        ->and($crmFields)->toContain('fieldValues.social_network')
        ->and($crmFields)->toContain('fieldValues.sentiment');
});

it('prevents duplicate activecampaign connection via api key', function () {
    insertActiveCampaignConnection($this->orgId, $this->user['id']);

    $response = $this->postJson('/api/v1/crm/connect-api-key', [
        'provider' => 'activecampaign',
        'api_key' => 'another-key',
        'account_name' => 'Another AC',
    ], $this->headers);

    $response->assertStatus(422);
});

it('returns 401 without auth on connect-api-key', function () {
    $response = $this->postJson('/api/v1/crm/connect-api-key', [
        'provider' => 'activecampaign',
        'api_key' => 'key',
        'account_name' => 'Test',
    ]);

    $response->assertStatus(401);
});
