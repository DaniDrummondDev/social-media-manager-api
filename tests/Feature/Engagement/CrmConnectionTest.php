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

function insertCrmConnection(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('crm_connections')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'provider' => 'hubspot',
        'access_token' => encryptCrmToken('access-token-123'),
        'refresh_token' => encryptCrmToken('refresh-token-456'),
        'token_expires_at' => now()->addHour()->toDateTimeString(),
        'external_account_id' => 'hub-ext-1',
        'account_name' => 'My HubSpot',
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

it('initiates CRM connection 200', function () {
    $response = $this->postJson('/api/v1/crm/connect', [
        'provider' => 'hubspot',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'authorization_url',
                'state',
            ],
        ]);

    expect($response->json('data.state'))->toBeString()->not->toBeEmpty();
});

it('validates provider on connect', function () {
    $response = $this->postJson('/api/v1/crm/connect', [
        'provider' => 'invalid-crm',
    ], $this->headers);

    $response->assertStatus(422);
});

it('lists connections 200', function () {
    insertCrmConnection($this->orgId, $this->user['id']);
    insertCrmConnection($this->orgId, $this->user['id'], ['provider' => 'pipedrive', 'external_account_id' => 'pipe-1', 'account_name' => 'My Pipedrive']);

    $response = $this->getJson('/api/v1/crm/connections', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(2);
});

it('shows single connection 200', function () {
    $connId = insertCrmConnection($this->orgId, $this->user['id']);

    $response = $this->getJson("/api/v1/crm/connections/{$connId}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'provider',
                    'external_account_id',
                    'account_name',
                    'status',
                    'connected_by',
                ],
            ],
        ]);

    expect($response->json('data.attributes.provider'))->toBe('hubspot');
});

it('returns 422 when connection not found on show', function () {
    $fakeId = (string) Str::uuid();

    $response = $this->getJson("/api/v1/crm/connections/{$fakeId}", $this->headers);

    $response->assertStatus(422);
});

it('tests connection 200', function () {
    $connId = insertCrmConnection($this->orgId, $this->user['id']);

    $response = $this->postJson("/api/v1/crm/connections/{$connId}/test", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.status', 'connected');
});

it('deletes connection 204', function () {
    $connId = insertCrmConnection($this->orgId, $this->user['id']);

    $response = $this->deleteJson("/api/v1/crm/connections/{$connId}", [], $this->headers);

    $response->assertStatus(204);

    $this->assertDatabaseHas('crm_connections', [
        'id' => $connId,
        'status' => 'revoked',
    ]);
});

it('prevents duplicate connection for same provider', function () {
    insertCrmConnection($this->orgId, $this->user['id']);

    $response = $this->postJson('/api/v1/crm/connect', [
        'provider' => 'hubspot',
    ], $this->headers);

    $response->assertStatus(422);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/crm/connections');

    $response->assertStatus(401);
});
