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

    // Create campaign + content (ready) + social account
    $this->campaignId = insertCampaignSched($this->orgId, $this->user['id']);
    $this->contentId = insertContentSched($this->orgId, $this->user['id'], $this->campaignId, ['status' => 'ready']);
    $this->accountId = insertSocialAccountSched($this->orgId, $this->user['id']);
});

function insertCampaignSched(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('campaigns')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'created_by' => $userId,
        'name' => 'Test Campaign '.Str::random(4),
        'description' => null,
        'starts_at' => null,
        'ends_at' => null,
        'status' => 'draft',
        'tags' => json_encode([]),
        'deleted_at' => null,
        'purge_at' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

function insertContentSched(string $orgId, string $userId, string $campaignId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('contents')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'campaign_id' => $campaignId,
        'created_by' => $userId,
        'title' => 'Test Content '.Str::random(4),
        'body' => 'Test body content',
        'hashtags' => json_encode([]),
        'status' => 'draft',
        'ai_generation_id' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'deleted_at' => null,
        'purge_at' => null,
    ], $overrides));

    return $id;
}

function insertSocialAccountSched(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('social_accounts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'connected_by' => $userId,
        'provider' => 'instagram',
        'provider_user_id' => 'ig-'.Str::random(6),
        'username' => 'user_'.Str::random(4),
        'display_name' => 'Test Account',
        'profile_picture_url' => null,
        'access_token' => 'encrypted-access-token',
        'refresh_token' => 'encrypted-refresh-token',
        'token_expires_at' => now()->addHour()->toDateTimeString(),
        'scopes' => json_encode(['read', 'write']),
        'status' => 'connected',
        'last_synced_at' => null,
        'connected_at' => now()->toDateTimeString(),
        'disconnected_at' => null,
        'metadata' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'deleted_at' => null,
        'purge_at' => null,
    ], $overrides));

    return $id;
}

it('schedules post — 201', function () {
    $scheduledAt = now()->addHour()->format('Y-m-d\TH:i:s\Z');

    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$this->contentId}/schedule",
        [
            'scheduled_at' => $scheduledAt,
            'social_account_ids' => [$this->accountId],
        ],
    );

    $response->assertStatus(201)
        ->assertJsonPath('data.content_id', $this->contentId)
        ->assertJsonCount(1, 'data.scheduled_posts')
        ->assertJsonPath('data.scheduled_posts.0.type', 'scheduled_post')
        ->assertJsonPath('data.scheduled_posts.0.attributes.status', 'pending');
});

it('schedules post to multiple accounts — 201', function () {
    $account2 = insertSocialAccountSched($this->orgId, $this->user['id'], ['provider' => 'tiktok']);
    $scheduledAt = now()->addHour()->format('Y-m-d\TH:i:s\Z');

    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$this->contentId}/schedule",
        [
            'scheduled_at' => $scheduledAt,
            'social_account_ids' => [$this->accountId, $account2],
        ],
    );

    $response->assertStatus(201)
        ->assertJsonCount(2, 'data.scheduled_posts');
});

it('rejects schedule without scheduled_at — 422', function () {
    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$this->contentId}/schedule",
        [
            'social_account_ids' => [$this->accountId],
        ],
    );

    $response->assertStatus(422);
});

it('rejects schedule with empty social_account_ids — 422', function () {
    $scheduledAt = now()->addHour()->format('Y-m-d\TH:i:s\Z');

    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$this->contentId}/schedule",
        [
            'scheduled_at' => $scheduledAt,
            'social_account_ids' => [],
        ],
    );

    $response->assertStatus(422);
});

it('rejects schedule with past date — 422', function () {
    $pastDate = now()->subHour()->format('Y-m-d\TH:i:s\Z');

    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$this->contentId}/schedule",
        [
            'scheduled_at' => $pastDate,
            'social_account_ids' => [$this->accountId],
        ],
    );

    $response->assertStatus(422);
});

it('rejects schedule for non-existent content — 404', function () {
    $fakeContentId = (string) Str::uuid();
    $scheduledAt = now()->addHour()->format('Y-m-d\TH:i:s\Z');

    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$fakeContentId}/schedule",
        [
            'scheduled_at' => $scheduledAt,
            'social_account_ids' => [$this->accountId],
        ],
    );

    $response->assertStatus(404);
});

it('rejects schedule for draft content — 422', function () {
    $draftContentId = insertContentSched($this->orgId, $this->user['id'], $this->campaignId, ['status' => 'draft']);
    $scheduledAt = now()->addHour()->format('Y-m-d\TH:i:s\Z');

    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$draftContentId}/schedule",
        [
            'scheduled_at' => $scheduledAt,
            'social_account_ids' => [$this->accountId],
        ],
    );

    $response->assertStatus(422);
});

it('publishes now — 202', function () {
    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$this->contentId}/publish-now",
        [
            'social_account_ids' => [$this->accountId],
        ],
    );

    $response->assertStatus(202)
        ->assertJsonPath('data.content_id', $this->contentId)
        ->assertJsonCount(1, 'data.scheduled_posts')
        ->assertJsonPath('data.scheduled_posts.0.attributes.status', 'dispatched');
});

it('rejects publish-now without social_account_ids — 422', function () {
    $response = $this->withHeaders($this->headers)->postJson(
        "/api/v1/contents/{$this->contentId}/publish-now",
        [],
    );

    $response->assertStatus(422);
});

it('requires authentication — 401', function () {
    $response = $this->postJson("/api/v1/contents/{$this->contentId}/schedule", []);

    $response->assertStatus(401);
});
