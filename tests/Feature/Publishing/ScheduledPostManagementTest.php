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

    $this->campaignId = insertCampaignPub($this->orgId, $this->user['id']);
    $this->contentId = insertContentPub($this->orgId, $this->user['id'], $this->campaignId, ['status' => 'scheduled']);
    $this->accountId = insertSocialAccountPub($this->orgId, $this->user['id']);
});

function insertCampaignPub(string $orgId, string $userId, array $overrides = []): string
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

function insertContentPub(string $orgId, string $userId, string $campaignId, array $overrides = []): string
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

function insertSocialAccountPub(string $orgId, string $userId, array $overrides = []): string
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

function insertScheduledPost(string $orgId, string $contentId, string $accountId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('scheduled_posts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'content_id' => $contentId,
        'social_account_id' => $accountId,
        'scheduled_by' => $userId,
        'scheduled_at' => now()->addHour()->toDateTimeString(),
        'status' => 'pending',
        'published_at' => null,
        'external_post_id' => null,
        'external_post_url' => null,
        'attempts' => 0,
        'max_attempts' => 3,
        'last_attempted_at' => null,
        'last_error' => null,
        'next_retry_at' => null,
        'dispatched_at' => null,
        'idempotency_key' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('lists scheduled posts — 200', function () {
    insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id']);
    insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id'], [
        'social_account_id' => insertSocialAccountPub($this->orgId, $this->user['id'], ['provider' => 'tiktok']),
    ]);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/scheduled-posts');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lists scheduled posts with status filter — 200', function () {
    insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id'], ['status' => 'pending']);
    insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id'], [
        'social_account_id' => insertSocialAccountPub($this->orgId, $this->user['id']),
        'status' => 'published',
        'published_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/scheduled-posts?status=pending');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows scheduled post — 200', function () {
    $postId = insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id']);

    $response = $this->withHeaders($this->headers)->getJson("/api/v1/scheduled-posts/{$postId}");

    $response->assertOk()
        ->assertJsonPath('data.id', $postId)
        ->assertJsonPath('data.type', 'scheduled_post')
        ->assertJsonPath('data.attributes.status', 'pending');
});

it('returns 404 for non-existent scheduled post — show', function () {
    $fakeId = (string) Str::uuid();

    $response = $this->withHeaders($this->headers)->getJson("/api/v1/scheduled-posts/{$fakeId}");

    $response->assertStatus(404);
});

it('cancels scheduled post — 200', function () {
    $postId = insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id']);

    $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/scheduled-posts/{$postId}");

    $response->assertOk()
        ->assertJsonPath('data.message', 'Agendamento cancelado com sucesso.')
        ->assertJsonPath('data.content_id', $this->contentId);
});

it('reschedules post — 200', function () {
    $postId = insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id']);
    $newScheduledAt = now()->addHours(2)->format('Y-m-d\TH:i:s\Z');

    $response = $this->withHeaders($this->headers)->putJson("/api/v1/scheduled-posts/{$postId}", [
        'scheduled_at' => $newScheduledAt,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $postId)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.message', 'Reagendado com sucesso.');
});

it('rejects reschedule without scheduled_at — 422', function () {
    $postId = insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id']);

    $response = $this->withHeaders($this->headers)->putJson("/api/v1/scheduled-posts/{$postId}", []);

    $response->assertStatus(422);
});

it('retries failed post — 202', function () {
    $postId = insertScheduledPost($this->orgId, $this->contentId, $this->accountId, $this->user['id'], [
        'status' => 'failed',
        'attempts' => 1,
        'max_attempts' => 3,
        'last_error' => json_encode(['code' => 'API_ERROR', 'message' => 'Timeout', 'is_permanent' => false]),
        'last_attempted_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->postJson("/api/v1/scheduled-posts/{$postId}/retry");

    $response->assertStatus(202)
        ->assertJsonPath('data.id', $postId)
        ->assertJsonPath('data.status', 'publishing')
        ->assertJsonPath('data.message', 'Publicação reenviada para processamento.');
});

it('isolates scheduled posts by organization', function () {
    $otherUser = $this->createUserInDb(['email' => 'other-pub@example.com']);
    $otherOrg = $this->createOrgWithOwner($otherUser['id'], ['name' => 'Other Pub Org', 'slug' => 'other-pub-org']);
    $otherOrgId = $otherOrg['org']['id'];

    $otherCampaign = insertCampaignPub($otherOrgId, $otherUser['id']);
    $otherContent = insertContentPub($otherOrgId, $otherUser['id'], $otherCampaign, ['status' => 'scheduled']);
    $otherAccount = insertSocialAccountPub($otherOrgId, $otherUser['id']);
    insertScheduledPost($otherOrgId, $otherContent, $otherAccount, $otherUser['id']);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/scheduled-posts');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires authentication — 401', function () {
    $response = $this->getJson('/api/v1/scheduled-posts');
    $response->assertStatus(401);
});
