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

    $this->connId = (string) Str::uuid();
    DB::table('crm_connections')->insert([
        'id' => $this->connId,
        'organization_id' => $this->orgId,
        'provider' => 'hubspot',
        'access_token' => encryptCrmToken('token'),
        'refresh_token' => encryptCrmToken('refresh'),
        'token_expires_at' => now()->addHour()->toDateTimeString(),
        'external_account_id' => 'hub-1',
        'account_name' => 'HubSpot',
        'status' => 'connected',
        'settings' => json_encode([]),
        'connected_by' => $this->user['id'],
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('returns default mappings when none configured 200', function () {
    $response = $this->getJson("/api/v1/crm/connections/{$this->connId}/mappings", $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('updates mappings 200', function () {
    $response = $this->putJson("/api/v1/crm/connections/{$this->connId}/mappings", [
        'mappings' => [
            ['smm_field' => 'name', 'crm_field' => 'full_name', 'transform' => 'uppercase'],
            ['smm_field' => 'email', 'crm_field' => 'email_address'],
        ],
    ], $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(2);

    $first = $response->json('data.0');
    expect($first['smm_field'])->toBe('name')
        ->and($first['crm_field'])->toBe('full_name')
        ->and($first['transform'])->toBe('uppercase');
});

it('validates mappings on update', function () {
    $response = $this->putJson("/api/v1/crm/connections/{$this->connId}/mappings", [
        'mappings' => [],
    ], $this->headers);

    $response->assertStatus(422);
});

it('validates mapping fields are required', function () {
    $response = $this->putJson("/api/v1/crm/connections/{$this->connId}/mappings", [
        'mappings' => [
            ['smm_field' => 'name'],
        ],
    ], $this->headers);

    $response->assertStatus(422);
});

it('resets to defaults 200', function () {
    // First set custom mappings
    $this->putJson("/api/v1/crm/connections/{$this->connId}/mappings", [
        'mappings' => [
            ['smm_field' => 'custom', 'crm_field' => 'custom_crm'],
        ],
    ], $this->headers);

    // Then reset
    $response = $this->postJson("/api/v1/crm/connections/{$this->connId}/mappings/reset", [], $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('returns 422 for non-existent connection', function () {
    $fakeId = (string) Str::uuid();

    $response = $this->getJson("/api/v1/crm/connections/{$fakeId}/mappings", $this->headers);

    $response->assertStatus(422);
});
