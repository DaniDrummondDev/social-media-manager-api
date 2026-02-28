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

it('lists sync logs 200', function () {
    DB::table('crm_sync_logs')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'crm_connection_id' => $this->connId,
        'direction' => 'outbound',
        'entity_type' => 'contact',
        'action' => 'create',
        'status' => 'success',
        'external_id' => 'crm-123',
        'error_message' => null,
        'payload' => json_encode(['name' => 'John']),
        'created_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson("/api/v1/crm/connections/{$this->connId}/logs", $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);

    $log = $response->json('data.0');
    expect($log['type'])->toBe('crm_sync_log')
        ->and($log['attributes']['direction'])->toBe('outbound')
        ->and($log['attributes']['entity_type'])->toBe('contact')
        ->and($log['attributes']['status'])->toBe('success');
});

it('returns empty list when no logs 200', function () {
    $response = $this->getJson("/api/v1/crm/connections/{$this->connId}/logs", $this->headers);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(0);
});

it('supports cursor pagination', function () {
    for ($i = 0; $i < 3; $i++) {
        DB::table('crm_sync_logs')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->orgId,
            'crm_connection_id' => $this->connId,
            'direction' => 'outbound',
            'entity_type' => 'contact',
            'action' => 'create',
            'status' => 'success',
            'created_at' => now()->subMinutes($i)->toDateTimeString(),
        ]);
    }

    $response = $this->getJson("/api/v1/crm/connections/{$this->connId}/logs?limit=2", $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(2);
});

it('returns 422 for non-existent connection', function () {
    $fakeId = (string) Str::uuid();

    $response = $this->getJson("/api/v1/crm/connections/{$fakeId}/logs", $this->headers);

    $response->assertStatus(422);
});

it('returns 401 without auth', function () {
    $response = $this->getJson("/api/v1/crm/connections/{$this->connId}/logs");

    $response->assertStatus(401);
});
