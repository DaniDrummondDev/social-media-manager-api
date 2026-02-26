<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Create a listening query that mentions can reference
    $this->queryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $this->queryId,
        'organization_id' => $this->orgId,
        'name' => 'Test Query',
        'type' => 'keyword',
        'value' => 'test keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

function insertMention(string $orgId, string $queryId, array $overrides = []): string
{
    $mentionId = $overrides['id'] ?? Str::uuid()->toString();
    $now = now();

    DB::table('mentions')->insert(array_merge([
        'id' => $mentionId,
        'query_id' => $queryId,
        'organization_id' => $orgId,
        'platform' => 'instagram',
        'external_id' => 'ext-' . Str::random(10),
        'author_username' => 'testuser',
        'author_display_name' => 'Test User',
        'author_follower_count' => 1500,
        'profile_url' => 'https://instagram.com/testuser',
        'content' => 'This is a test mention about the brand.',
        'url' => 'https://instagram.com/p/abc123',
        'sentiment' => 'positive',
        'sentiment_score' => 0.8500,
        'reach' => 1000,
        'engagement_count' => 50,
        'is_flagged' => false,
        'is_read' => false,
        'published_at' => $now->toDateTimeString(),
        'detected_at' => $now->toDateTimeString(),
    ], $overrides));

    return $mentionId;
}

it('lists mentions with 200', function () {
    insertMention($this->orgId, $this->queryId, ['author_username' => 'user_alpha']);
    insertMention($this->orgId, $this->queryId, ['author_username' => 'user_beta']);

    $response = $this->getJson('/api/v1/mentions', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'query_id',
                        'platform',
                        'external_id',
                        'author_username',
                        'author_display_name',
                        'content',
                        'sentiment',
                        'is_flagged',
                        'is_read',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('shows a single mention with 200', function () {
    $mentionId = insertMention($this->orgId, $this->queryId, [
        'author_username' => 'detail_user',
        'content' => 'Detailed mention content',
    ]);

    $response = $this->getJson("/api/v1/mentions/{$mentionId}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'query_id',
                    'platform',
                    'external_id',
                    'author_username',
                    'author_display_name',
                    'author_follower_count',
                    'profile_url',
                    'content',
                    'url',
                    'sentiment',
                    'sentiment_score',
                    'reach',
                    'engagement_count',
                    'is_flagged',
                    'is_read',
                    'published_at',
                    'detected_at',
                ],
            ],
        ]);

    expect($response->json('data.id'))->toBe($mentionId);
    expect($response->json('data.type'))->toBe('mention');
    expect($response->json('data.attributes.author_username'))->toBe('detail_user');
    expect($response->json('data.attributes.content'))->toBe('Detailed mention content');
});

it('flags a mention with 200', function () {
    $mentionId = insertMention($this->orgId, $this->queryId, ['is_flagged' => false]);

    $response = $this->postJson("/api/v1/mentions/{$mentionId}/flag", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'is_flagged',
                ],
            ],
        ]);

    expect($response->json('data.attributes.is_flagged'))->toBeTrue();
});

it('unflags a mention with 200', function () {
    $mentionId = insertMention($this->orgId, $this->queryId, ['is_flagged' => true]);

    $response = $this->postJson("/api/v1/mentions/{$mentionId}/unflag", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'is_flagged',
                ],
            ],
        ]);

    expect($response->json('data.attributes.is_flagged'))->toBeFalse();
});

it('marks mentions as read with 200', function () {
    $mentionId1 = insertMention($this->orgId, $this->queryId, ['is_read' => false]);
    $mentionId2 = insertMention($this->orgId, $this->queryId, ['is_read' => false]);

    $response = $this->postJson('/api/v1/mentions/mark-read', [
        'mention_ids' => [$mentionId1, $mentionId2],
    ], $this->headers);

    $response->assertStatus(200);

    expect($response->json('data.marked'))->toBe(2);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/mentions');

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org mentions', function () {
    $ownMentionId = insertMention($this->orgId, $this->queryId, [
        'author_username' => 'own_org_user',
    ]);

    // Create another org with a different user
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];

    $otherQueryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $otherQueryId,
        'organization_id' => $otherOrgId,
        'name' => 'Other Query',
        'type' => 'keyword',
        'value' => 'other keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $otherMentionId = insertMention($otherOrgId, $otherQueryId, [
        'author_username' => 'other_org_user',
    ]);

    // List should only return own org mentions
    $listResponse = $this->getJson('/api/v1/mentions', $this->headers);

    $listResponse->assertStatus(200);

    $ids = array_column($listResponse->json('data'), 'id');
    expect($ids)->toContain($ownMentionId);
    expect($ids)->not->toContain($otherMentionId);
});
