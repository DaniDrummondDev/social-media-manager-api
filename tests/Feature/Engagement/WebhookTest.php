<?php

declare(strict_types=1);

use App\Domain\Engagement\ValueObjects\WebhookSecret;
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
});

it('creates webhook 201 with secret', function () {
    $response = $this->postJson('/api/v1/webhooks', [
        'name' => 'Test Webhook',
        'url' => 'https://example.com/webhook',
        'events' => ['comment.created'],
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'url',
                    'secret',
                    'events',
                    'is_active',
                ],
            ],
        ]);

    $secret = $response->json('data.attributes.secret');
    expect($secret)->toStartWith('whsec_');
});

it('lists webhooks 200', function () {
    DB::table('webhook_endpoints')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'name' => 'Existing',
        'url' => 'https://example.com/wh',
        'secret' => (string) WebhookSecret::generate(),
        'events' => json_encode(['comment.created']),
        'is_active' => true,
        'failure_count' => 0,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/webhooks', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('updates webhook 200', function () {
    $webhookId = (string) Str::uuid();
    DB::table('webhook_endpoints')->insert([
        'id' => $webhookId,
        'organization_id' => $this->orgId,
        'name' => 'Original',
        'url' => 'https://example.com/wh',
        'secret' => (string) WebhookSecret::generate(),
        'events' => json_encode(['comment.created']),
        'is_active' => true,
        'failure_count' => 0,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->putJson("/api/v1/webhooks/{$webhookId}", [
        'name' => 'Updated Webhook',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.name', 'Updated Webhook');
});

it('deletes webhook 204', function () {
    $webhookId = (string) Str::uuid();
    DB::table('webhook_endpoints')->insert([
        'id' => $webhookId,
        'organization_id' => $this->orgId,
        'name' => 'To Delete',
        'url' => 'https://example.com/wh',
        'secret' => (string) WebhookSecret::generate(),
        'events' => json_encode(['comment.created']),
        'is_active' => true,
        'failure_count' => 0,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->deleteJson("/api/v1/webhooks/{$webhookId}", [], $this->headers);

    $response->assertStatus(204);
});

it('lists deliveries 200', function () {
    $webhookId = (string) Str::uuid();
    DB::table('webhook_endpoints')->insert([
        'id' => $webhookId,
        'organization_id' => $this->orgId,
        'name' => 'Webhook',
        'url' => 'https://example.com/wh',
        'secret' => (string) WebhookSecret::generate(),
        'events' => json_encode(['comment.created']),
        'is_active' => true,
        'failure_count' => 0,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson("/api/v1/webhooks/{$webhookId}/deliveries", $this->headers);

    $response->assertStatus(200);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/webhooks');

    $response->assertStatus(401);
});
