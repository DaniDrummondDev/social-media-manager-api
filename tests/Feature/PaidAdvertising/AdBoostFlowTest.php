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

function insertAdAccountForBoost(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();
    $encrypter = app(\App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface::class);

    DB::table('ad_accounts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'connected_by' => $userId,
        'provider' => 'meta',
        'provider_account_id' => 'act_' . substr($id, 0, 8),
        'provider_account_name' => 'Test Ad Account',
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

function insertAudienceForBoost(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('audiences')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'name' => 'Boost Audience ' . substr($id, 0, 8),
        'targeting_spec' => json_encode([
            'demographics' => ['age_min' => 18, 'age_max' => 65, 'genders' => ['all']],
            'locations' => [['country' => 'BR']],
            'interests' => [],
        ]),
        'provider_audience_ids' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

function insertScheduledPostForBoost(string $orgId, string $userId): string
{
    $socialAccountId = (string) Str::uuid();
    DB::table('social_accounts')->insert([
        'id' => $socialAccountId,
        'organization_id' => $orgId,
        'connected_by' => $userId,
        'provider' => 'instagram',
        'provider_user_id' => 'ig_' . substr($socialAccountId, 0, 8),
        'username' => 'testuser',
        'display_name' => 'Test User',
        'access_token' => 'test-token',
        'refresh_token' => null,
        'token_expires_at' => now()->addHours(2)->toDateTimeString(),
        'scopes' => json_encode([]),
        'status' => 'connected',
        'connected_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $campaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $orgId,
        'name' => 'Test Campaign',
        'status' => 'active',
        'created_by' => $userId,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $contentId = (string) Str::uuid();
    DB::table('contents')->insert([
        'id' => $contentId,
        'campaign_id' => $campaignId,
        'organization_id' => $orgId,
        'body' => 'Test content for boost',
        'status' => 'published',
        'created_by' => $userId,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $postId = (string) Str::uuid();
    DB::table('scheduled_posts')->insert([
        'id' => $postId,
        'organization_id' => $orgId,
        'content_id' => $contentId,
        'social_account_id' => $socialAccountId,
        'scheduled_by' => $userId,
        'scheduled_at' => now()->subDay()->toDateTimeString(),
        'status' => 'published',
        'published_at' => now()->subDay()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    return $postId;
}

function insertAdBoost(string $orgId, string $accountId, string $audienceId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();
    $postId = $overrides['scheduled_post_id'] ?? (string) Str::uuid();

    DB::table('ad_boosts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'scheduled_post_id' => $postId,
        'ad_account_id' => $accountId,
        'audience_id' => $audienceId,
        'budget_amount_cents' => 5000,
        'budget_currency' => 'USD',
        'budget_type' => 'daily',
        'duration_days' => 7,
        'objective' => 'reach',
        'status' => 'draft',
        'external_ids' => null,
        'rejection_reason' => null,
        'started_at' => null,
        'completed_at' => null,
        'created_by' => $userId,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('creates boost 201', function () {
    $accountId = insertAdAccountForBoost($this->orgId, $this->user['id']);
    $audienceId = insertAudienceForBoost($this->orgId);
    $scheduledPostId = insertScheduledPostForBoost($this->orgId, $this->user['id']);

    $response = $this->postJson('/api/v1/ads/boosts', [
        'scheduled_post_id' => $scheduledPostId,
        'ad_account_id' => $accountId,
        'audience_id' => $audienceId,
        'budget_amount_cents' => 5000,
        'budget_currency' => 'USD',
        'budget_type' => 'daily',
        'duration_days' => 7,
        'objective' => 'reach',
    ], $this->headers);

    $response->assertStatus(201);
    expect($response->json('data.type'))->toBe('ad_boost')
        ->and($response->json('data.attributes.status'))->toBe('draft')
        ->and($response->json('data.attributes.budget_amount_cents'))->toBe(5000);
});

it('lists boosts with cursor pagination 200', function () {
    $accountId = insertAdAccountForBoost($this->orgId, $this->user['id']);
    $audienceId = insertAudienceForBoost($this->orgId);
    insertAdBoost($this->orgId, $accountId, $audienceId, $this->user['id']);
    insertAdBoost($this->orgId, $accountId, $audienceId, $this->user['id']);

    $response = $this->getJson('/api/v1/ads/boosts', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(2);
    $response->assertJsonStructure(['meta' => ['pagination' => ['next_cursor']]]);
});

it('shows boost 200', function () {
    $accountId = insertAdAccountForBoost($this->orgId, $this->user['id']);
    $audienceId = insertAudienceForBoost($this->orgId);
    $boostId = insertAdBoost($this->orgId, $accountId, $audienceId, $this->user['id']);

    $response = $this->getJson("/api/v1/ads/boosts/{$boostId}", $this->headers);

    $response->assertStatus(200);
    expect($response->json('data.id'))->toBe($boostId)
        ->and($response->json('data.attributes.objective'))->toBe('reach');
});

it('cancels boost 200', function () {
    $accountId = insertAdAccountForBoost($this->orgId, $this->user['id']);
    $audienceId = insertAudienceForBoost($this->orgId);
    $boostId = insertAdBoost($this->orgId, $accountId, $audienceId, $this->user['id'], [
        'status' => 'active',
        'started_at' => now()->subDays(2)->toDateTimeString(),
    ]);

    $response = $this->postJson("/api/v1/ads/boosts/{$boostId}/cancel", [], $this->headers);

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe('cancelled');
});
