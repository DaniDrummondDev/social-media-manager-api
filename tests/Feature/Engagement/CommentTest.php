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

    // Create social account and content for comments
    $this->accountId = (string) Str::uuid();
    DB::table('social_accounts')->insert([
        'id' => $this->accountId,
        'organization_id' => $this->orgId,
        'connected_by' => $this->user['id'],
        'provider' => 'instagram',
        'provider_user_id' => 'ig-001',
        'username' => '@test',
        'display_name' => 'Test',
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'token_expires_at' => now()->addDays(30)->toDateTimeString(),
        'scopes' => json_encode(['read']),
        'status' => 'connected',
        'connected_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $campaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->user['id'],
        'name' => 'Campaign',
        'status' => 'draft',
        'tags' => json_encode([]),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->contentId = (string) Str::uuid();
    DB::table('contents')->insert([
        'id' => $this->contentId,
        'organization_id' => $this->orgId,
        'campaign_id' => $campaignId,
        'created_by' => $this->user['id'],
        'title' => 'Content',
        'body' => 'Body',
        'hashtags' => json_encode([]),
        'status' => 'ready',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('lists comments 200', function () {
    $commentId = (string) Str::uuid();
    DB::table('comments')->insert([
        'id' => $commentId,
        'organization_id' => $this->orgId,
        'content_id' => $this->contentId,
        'social_account_id' => $this->accountId,
        'provider' => 'instagram',
        'external_comment_id' => 'ext-1',
        'author_name' => 'Author',
        'text' => 'Great post!',
        'sentiment' => 'positive',
        'is_read' => false,
        'is_from_owner' => false,
        'replied_by_automation' => false,
        'commented_at' => now()->toDateTimeString(),
        'captured_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/comments', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ],
        ]);
});

it('filters by sentiment', function () {
    DB::table('comments')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'content_id' => $this->contentId,
        'social_account_id' => $this->accountId,
        'provider' => 'instagram',
        'external_comment_id' => 'ext-pos',
        'author_name' => 'Author',
        'text' => 'Great!',
        'sentiment' => 'positive',
        'is_read' => false,
        'is_from_owner' => false,
        'replied_by_automation' => false,
        'commented_at' => now()->toDateTimeString(),
        'captured_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('comments')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'content_id' => $this->contentId,
        'social_account_id' => $this->accountId,
        'provider' => 'instagram',
        'external_comment_id' => 'ext-neg',
        'author_name' => 'Author',
        'text' => 'Bad!',
        'sentiment' => 'negative',
        'is_read' => false,
        'is_from_owner' => false,
        'replied_by_automation' => false,
        'commented_at' => now()->toDateTimeString(),
        'captured_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/comments?sentiment=positive', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});

it('marks as read', function () {
    $commentId = (string) Str::uuid();
    DB::table('comments')->insert([
        'id' => $commentId,
        'organization_id' => $this->orgId,
        'content_id' => $this->contentId,
        'social_account_id' => $this->accountId,
        'provider' => 'instagram',
        'external_comment_id' => 'ext-read',
        'author_name' => 'Author',
        'text' => 'Comment',
        'is_read' => false,
        'is_from_owner' => false,
        'replied_by_automation' => false,
        'commented_at' => now()->toDateTimeString(),
        'captured_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->putJson("/api/v1/comments/{$commentId}/read", [], $this->headers);

    $response->assertStatus(200);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/comments');

    $response->assertStatus(401);
});
