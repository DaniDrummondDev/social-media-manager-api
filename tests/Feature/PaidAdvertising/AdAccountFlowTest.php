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

    // Subscription with Professional plan (paid_advertising unlimited)
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

function insertAdAccount(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();
    $encrypter = app(\App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface::class);

    DB::table('ad_accounts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'connected_by' => $userId,
        'provider' => 'meta',
        'provider_account_id' => 'act_123456',
        'provider_account_name' => 'My Meta Ads',
        'encrypted_access_token' => $encrypter->encrypt('test-access-token'),
        'encrypted_refresh_token' => null,
        'token_expires_at' => now()->addHours(2)->toDateTimeString(),
        'scopes' => json_encode(['ads_management', 'ads_read']),
        'status' => 'active',
        'metadata' => json_encode([]),
        'connected_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('initiates meta ad account connect 200', function () {
    $response = $this->postJson('/api/v1/ads/accounts/connect', [
        'provider' => 'meta',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'authorization_url',
                'state',
            ],
        ]);

    expect($response->json('data.authorization_url'))
        ->toContain('facebook.com');
});

it('lists ad accounts 200', function () {
    insertAdAccount($this->orgId, $this->user['id']);

    $response = $this->getJson('/api/v1/ads/accounts', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.attributes.provider'))->toBe('meta');
});

it('shows ad account 200', function () {
    $accountId = insertAdAccount($this->orgId, $this->user['id']);

    $response = $this->getJson("/api/v1/ads/accounts/{$accountId}", $this->headers);

    $response->assertStatus(200);
    expect($response->json('data.attributes.provider'))->toBe('meta')
        ->and($response->json('data.attributes.provider_account_name'))->toBe('My Meta Ads');
});

it('tests ad account connection 200', function () {
    $accountId = insertAdAccount($this->orgId, $this->user['id']);

    $response = $this->postJson("/api/v1/ads/accounts/{$accountId}/test", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'is_connected',
                'provider_account_name',
            ],
        ]);
});

it('disconnects ad account 204', function () {
    $accountId = insertAdAccount($this->orgId, $this->user['id']);

    $response = $this->deleteJson("/api/v1/ads/accounts/{$accountId}", [], $this->headers);

    $response->assertStatus(204);
    $this->assertDatabaseHas('ad_accounts', [
        'id' => $accountId,
        'status' => 'disconnected',
    ]);
});

it('returns 422 for invalid provider', function () {
    $response = $this->postJson('/api/v1/ads/accounts/connect', [
        'provider' => 'invalid_provider',
    ], $this->headers);

    $response->assertStatus(422);
});
