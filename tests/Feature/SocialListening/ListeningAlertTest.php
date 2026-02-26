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

    // Create a listening query that alerts can reference
    $this->queryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $this->queryId,
        'organization_id' => $this->orgId,
        'name' => 'Alert Test Query',
        'type' => 'keyword',
        'value' => 'alert keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

it('creates an alert with 201', function () {
    $response = $this->postJson('/api/v1/listening/alerts', [
        'name' => 'Volume Spike Alert',
        'query_ids' => [$this->queryId],
        'condition_type' => 'volume_spike',
        'threshold' => 50,
        'window_minutes' => 60,
        'channels' => ['email'],
        'cooldown_minutes' => 30,
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'organization_id',
                    'name',
                    'query_ids',
                    'condition_type',
                    'threshold',
                    'window_minutes',
                    'channels',
                    'cooldown_minutes',
                    'is_active',
                    'last_triggered_at',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('listening_alert');
    expect($response->json('data.attributes.name'))->toBe('Volume Spike Alert');
    expect($response->json('data.attributes.condition_type'))->toBe('volume_spike');
    expect($response->json('data.attributes.threshold'))->toBe(50);
    expect($response->json('data.attributes.window_minutes'))->toBe(60);
    expect($response->json('data.attributes.channels'))->toBe(['email']);
    expect($response->json('data.attributes.cooldown_minutes'))->toBe(30);
    expect($response->json('data.attributes.is_active'))->toBeTrue();
});

it('lists alerts with 200', function () {
    $now = now()->toDateTimeString();

    DB::table('listening_alerts')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'name' => 'Alert Alpha',
        'query_ids' => json_encode([$this->queryId]),
        'condition_type' => 'volume_spike',
        'threshold' => 20,
        'window_minutes' => 30,
        'channels' => json_encode(['email']),
        'cooldown_minutes' => 60,
        'is_active' => true,
        'last_triggered_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('listening_alerts')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'name' => 'Alert Beta',
        'query_ids' => json_encode([$this->queryId]),
        'condition_type' => 'negative_sentiment_spike',
        'threshold' => 10,
        'window_minutes' => 60,
        'channels' => json_encode(['webhook']),
        'cooldown_minutes' => 120,
        'is_active' => true,
        'last_triggered_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson('/api/v1/listening/alerts', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'condition_type',
                        'threshold',
                        'is_active',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('updates an alert with 200', function () {
    $alertId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_alerts')->insert([
        'id' => $alertId,
        'organization_id' => $this->orgId,
        'name' => 'Old Alert Name',
        'query_ids' => json_encode([$this->queryId]),
        'condition_type' => 'volume_spike',
        'threshold' => 20,
        'window_minutes' => 30,
        'channels' => json_encode(['email']),
        'cooldown_minutes' => 60,
        'is_active' => true,
        'last_triggered_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->patchJson("/api/v1/listening/alerts/{$alertId}", [
        'name' => 'Updated Alert Name',
        'condition_type' => 'volume_spike',
        'threshold' => 100,
        'window_minutes' => 45,
        'channels' => ['email', 'webhook'],
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'threshold',
                    'window_minutes',
                    'channels',
                ],
            ],
        ]);

    expect($response->json('data.attributes.name'))->toBe('Updated Alert Name');
    expect($response->json('data.attributes.threshold'))->toBe(100);
    expect($response->json('data.attributes.window_minutes'))->toBe(45);
    expect($response->json('data.attributes.channels'))->toBe(['email', 'webhook']);
});

it('deletes an alert with 204', function () {
    $alertId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_alerts')->insert([
        'id' => $alertId,
        'organization_id' => $this->orgId,
        'name' => 'To Delete Alert',
        'query_ids' => json_encode([$this->queryId]),
        'condition_type' => 'keyword_detected',
        'threshold' => 5,
        'window_minutes' => 15,
        'channels' => json_encode(['email']),
        'cooldown_minutes' => 30,
        'is_active' => true,
        'last_triggered_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->deleteJson("/api/v1/listening/alerts/{$alertId}", [], $this->headers);

    $response->assertStatus(204);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/listening/alerts');

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org alerts', function () {
    $now = now()->toDateTimeString();

    $ownAlertId = Str::uuid()->toString();

    DB::table('listening_alerts')->insert([
        'id' => $ownAlertId,
        'organization_id' => $this->orgId,
        'name' => 'Own Alert',
        'query_ids' => json_encode([$this->queryId]),
        'condition_type' => 'volume_spike',
        'threshold' => 10,
        'window_minutes' => 30,
        'channels' => json_encode(['email']),
        'cooldown_minutes' => 60,
        'is_active' => true,
        'last_triggered_at' => null,
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
        'name' => 'Other Query',
        'type' => 'keyword',
        'value' => 'other keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $otherAlertId = Str::uuid()->toString();

    DB::table('listening_alerts')->insert([
        'id' => $otherAlertId,
        'organization_id' => $otherOrgId,
        'name' => 'Other Org Alert',
        'query_ids' => json_encode([$otherQueryId]),
        'condition_type' => 'volume_spike',
        'threshold' => 10,
        'window_minutes' => 30,
        'channels' => json_encode(['email']),
        'cooldown_minutes' => 60,
        'is_active' => true,
        'last_triggered_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // List should only return own org alerts
    $listResponse = $this->getJson('/api/v1/listening/alerts', $this->headers);

    $listResponse->assertStatus(200);

    $ids = array_column($listResponse->json('data'), 'id');
    expect($ids)->toContain($ownAlertId);
    expect($ids)->not->toContain($otherAlertId);
});
