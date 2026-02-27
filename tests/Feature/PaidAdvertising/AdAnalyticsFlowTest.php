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

function insertAdAccountForAnalytics(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();
    $encrypter = app(\App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface::class);

    DB::table('ad_accounts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'connected_by' => $userId,
        'provider' => 'meta',
        'provider_account_id' => 'act_' . substr($id, 0, 8),
        'provider_account_name' => 'Analytics Ad Account',
        'encrypted_access_token' => $encrypter->encrypt('test-access-token'),
        'encrypted_refresh_token' => null,
        'token_expires_at' => now()->addHours(2)->toDateTimeString(),
        'scopes' => json_encode(['ads_management']),
        'status' => 'active',
        'metadata' => json_encode([]),
        'connected_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

function insertAdBoostForAnalytics(string $orgId, string $accountId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();
    $audienceId = (string) Str::uuid();

    DB::table('audiences')->insert([
        'id' => $audienceId,
        'organization_id' => $orgId,
        'name' => 'Analytics Audience ' . substr($id, 0, 8),
        'targeting_spec' => json_encode(['demographics' => ['age_min' => 18, 'age_max' => 65]]),
        'provider_audience_ids' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('ad_boosts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'scheduled_post_id' => (string) Str::uuid(),
        'ad_account_id' => $accountId,
        'audience_id' => $audienceId,
        'budget_amount_cents' => 10000,
        'budget_currency' => 'USD',
        'budget_type' => 'daily',
        'duration_days' => 14,
        'objective' => 'reach',
        'status' => 'active',
        'external_ids' => null,
        'rejection_reason' => null,
        'started_at' => now()->subDays(5)->toDateTimeString(),
        'completed_at' => null,
        'created_by' => $userId,
        'created_at' => now()->subDays(5)->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

function insertMetricSnapshot(string $boostId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('ad_metric_snapshots')->insert(array_merge([
        'id' => $id,
        'boost_id' => $boostId,
        'period' => 'daily',
        'impressions' => 5000,
        'reach' => 3500,
        'clicks' => 150,
        'spend_cents' => 2500,
        'spend_currency' => 'USD',
        'conversions' => 10,
        'ctr' => 3.0,
        'cpc' => 0.17,
        'cpm' => 0.50,
        'cost_per_conversion' => 2.50,
        'captured_at' => now()->subDay()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('gets analytics overview 200', function () {
    $accountId = insertAdAccountForAnalytics($this->orgId, $this->user['id']);
    $boostId = insertAdBoostForAnalytics($this->orgId, $accountId, $this->user['id']);
    insertMetricSnapshot($boostId);

    $from = now()->subDays(30)->format('Y-m-d');
    $to = now()->format('Y-m-d');

    $response = $this->getJson("/api/v1/ads/analytics/overview?from={$from}&to={$to}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes',
            ],
        ]);
});

it('gets spending history 200', function () {
    $accountId = insertAdAccountForAnalytics($this->orgId, $this->user['id']);
    $boostId = insertAdBoostForAnalytics($this->orgId, $accountId, $this->user['id']);
    insertMetricSnapshot($boostId);

    $from = now()->subDays(30)->format('Y-m-d');
    $to = now()->format('Y-m-d');

    $response = $this->getJson("/api/v1/ads/analytics/spending?from={$from}&to={$to}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes',
            ],
        ]);
});

it('exports spending report 200', function () {
    $response = $this->postJson('/api/v1/ads/analytics/export', [
        'from' => now()->subDays(30)->format('Y-m-d'),
        'to' => now()->format('Y-m-d'),
        'format' => 'csv',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'export_id',
                'status',
            ],
        ]);
});
