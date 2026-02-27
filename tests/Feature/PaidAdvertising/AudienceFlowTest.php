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

function insertAudienceForTest(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('audiences')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'name' => 'Test Audience',
        'targeting_spec' => json_encode([
            'demographics' => ['age_min' => 18, 'age_max' => 65, 'genders' => ['all']],
            'locations' => [['country' => 'BR']],
            'interests' => [['id' => 'int_1', 'name' => 'Technology']],
        ]),
        'provider_audience_ids' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

function insertAdAccountForAudience(string $orgId, string $userId, array $overrides = []): string
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

it('creates audience 201', function () {
    $response = $this->postJson('/api/v1/ads/audiences', [
        'name' => 'Young Professionals',
        'targeting_spec' => [
            'demographics' => ['age_min' => 25, 'age_max' => 40, 'genders' => ['all']],
            'locations' => [['country' => 'BR']],
            'interests' => [['id' => 'int_1', 'name' => 'Business']],
        ],
    ], $this->headers);

    $response->assertStatus(201);
    expect($response->json('data.type'))->toBe('audience')
        ->and($response->json('data.attributes.name'))->toBe('Young Professionals');
});

it('lists audiences 200', function () {
    insertAudienceForTest($this->orgId);

    $response = $this->getJson('/api/v1/ads/audiences', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('shows audience 200', function () {
    $audienceId = insertAudienceForTest($this->orgId);

    $response = $this->getJson("/api/v1/ads/audiences/{$audienceId}", $this->headers);

    $response->assertStatus(200);
    expect($response->json('data.attributes.name'))->toBe('Test Audience');
});

it('updates audience 200', function () {
    $audienceId = insertAudienceForTest($this->orgId);

    $response = $this->putJson("/api/v1/ads/audiences/{$audienceId}", [
        'name' => 'Updated Audience Name',
    ], $this->headers);

    $response->assertStatus(200);
    expect($response->json('data.attributes.name'))->toBe('Updated Audience Name');
});

it('deletes audience 204', function () {
    $audienceId = insertAudienceForTest($this->orgId);

    $response = $this->deleteJson("/api/v1/ads/audiences/{$audienceId}", [], $this->headers);

    $response->assertStatus(204);
    $this->assertDatabaseMissing('audiences', ['id' => $audienceId]);
});

it('returns 422 for duplicate audience name', function () {
    insertAudienceForTest($this->orgId, ['name' => 'Unique Audience']);

    $response = $this->postJson('/api/v1/ads/audiences', [
        'name' => 'Unique Audience',
        'targeting_spec' => [
            'demographics' => ['age_min' => 18, 'age_max' => 65],
        ],
    ], $this->headers);

    $response->assertStatus(422);
});

it('searches interests 200', function () {
    $accountId = insertAdAccountForAudience($this->orgId, $this->user['id']);

    $response = $this->getJson("/api/v1/ads/interests/search?account_id={$accountId}&query=technology", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'interests',
            ],
        ]);
});
