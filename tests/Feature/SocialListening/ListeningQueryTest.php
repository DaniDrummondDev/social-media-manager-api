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
});

it('creates a listening query with 201', function () {
    $response = $this->postJson('/api/v1/listening/queries', [
        'name' => 'Brand Mentions',
        'type' => 'keyword',
        'value' => 'my brand name',
        'platforms' => ['instagram', 'tiktok'],
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'organization_id',
                    'name',
                    'type',
                    'value',
                    'platforms',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('listening_query');
    expect($response->json('data.attributes.name'))->toBe('Brand Mentions');
    expect($response->json('data.attributes.type'))->toBe('keyword');
    expect($response->json('data.attributes.value'))->toBe('my brand name');
    expect($response->json('data.attributes.platforms'))->toBe(['instagram', 'tiktok']);
    expect($response->json('data.attributes.is_active'))->toBeTrue();
});

it('lists listening queries with 200', function () {
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'name' => 'Query Alpha',
        'type' => 'keyword',
        'value' => 'alpha keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('listening_queries')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'name' => 'Query Beta',
        'type' => 'hashtag',
        'value' => '#beta',
        'platforms' => json_encode(['tiktok', 'youtube']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson('/api/v1/listening/queries', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'type',
                        'value',
                        'platforms',
                        'is_active',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('updates a listening query with 200', function () {
    $queryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $queryId,
        'organization_id' => $this->orgId,
        'name' => 'Old Name',
        'type' => 'keyword',
        'value' => 'old value',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->patchJson("/api/v1/listening/queries/{$queryId}", [
        'name' => 'New Name',
        'value' => 'new value',
        'platforms' => ['instagram', 'youtube'],
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'value',
                    'platforms',
                ],
            ],
        ]);

    expect($response->json('data.attributes.name'))->toBe('New Name');
    expect($response->json('data.attributes.value'))->toBe('new value');
    expect($response->json('data.attributes.platforms'))->toBe(['instagram', 'youtube']);
});

it('pauses a query with 200', function () {
    $queryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $queryId,
        'organization_id' => $this->orgId,
        'name' => 'Active Query',
        'type' => 'mention',
        'value' => '@mybrand',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/listening/queries/{$queryId}/pause", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'is_active',
                ],
            ],
        ]);

    expect($response->json('data.attributes.is_active'))->toBeFalse();
});

it('resumes a paused query with 200', function () {
    $queryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $queryId,
        'organization_id' => $this->orgId,
        'name' => 'Paused Query',
        'type' => 'hashtag',
        'value' => '#paused',
        'platforms' => json_encode(['tiktok']),
        'status' => 'paused',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/listening/queries/{$queryId}/resume", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'is_active',
                ],
            ],
        ]);

    expect($response->json('data.attributes.is_active'))->toBeTrue();
});

it('deletes a query with 204', function () {
    $queryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $queryId,
        'organization_id' => $this->orgId,
        'name' => 'To Delete',
        'type' => 'competitor',
        'value' => 'competitor name',
        'platforms' => json_encode(['youtube']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->deleteJson("/api/v1/listening/queries/{$queryId}", [], $this->headers);

    $response->assertStatus(204);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/listening/queries');

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org queries', function () {
    $ownQueryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $ownQueryId,
        'organization_id' => $this->orgId,
        'name' => 'Own Query',
        'type' => 'keyword',
        'value' => 'own keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Create another org with a different user
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];

    $otherQueryId = Str::uuid()->toString();

    DB::table('listening_queries')->insert([
        'id' => $otherQueryId,
        'organization_id' => $otherOrgId,
        'name' => 'Other Org Query',
        'type' => 'keyword',
        'value' => 'other keyword',
        'platforms' => json_encode(['tiktok']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // List should only return own org queries
    $listResponse = $this->getJson('/api/v1/listening/queries', $this->headers);

    $listResponse->assertStatus(200);

    $ids = array_column($listResponse->json('data'), 'id');
    expect($ids)->toContain($ownQueryId);
    expect($ids)->not->toContain($otherQueryId);
});
