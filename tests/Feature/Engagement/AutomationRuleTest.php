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
});

it('creates automation rule 201', function () {
    $response = $this->postJson('/api/v1/automation-rules', [
        'name' => 'Welcome Rule',
        'priority' => 1,
        'conditions' => [
            ['field' => 'keyword', 'operator' => 'contains', 'value' => 'hello'],
        ],
        'action_type' => 'reply_fixed',
        'response_template' => 'Welcome!',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'priority',
                    'action_type',
                    'is_active',
                ],
            ],
        ]);
});

it('lists automation rules 200', function () {
    // Create a rule directly
    DB::table('automation_rules')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'name' => 'Existing Rule',
        'priority' => 1,
        'action_type' => 'reply_fixed',
        'response_template' => 'Thanks!',
        'delay_seconds' => 120,
        'daily_limit' => 100,
        'is_active' => true,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/automation-rules', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('updates automation rule 200', function () {
    $ruleId = (string) Str::uuid();
    DB::table('automation_rules')->insert([
        'id' => $ruleId,
        'organization_id' => $this->orgId,
        'name' => 'Original',
        'priority' => 1,
        'action_type' => 'reply_fixed',
        'response_template' => 'Thanks!',
        'delay_seconds' => 120,
        'daily_limit' => 100,
        'is_active' => true,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->putJson("/api/v1/automation-rules/{$ruleId}", [
        'name' => 'Updated Rule',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.name', 'Updated Rule');
});

it('deletes automation rule 204', function () {
    $ruleId = (string) Str::uuid();
    DB::table('automation_rules')->insert([
        'id' => $ruleId,
        'organization_id' => $this->orgId,
        'name' => 'To Delete',
        'priority' => 1,
        'action_type' => 'reply_fixed',
        'delay_seconds' => 120,
        'daily_limit' => 100,
        'is_active' => true,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->deleteJson("/api/v1/automation-rules/{$ruleId}", [], $this->headers);

    $response->assertStatus(204);
});

it('lists executions 200', function () {
    $ruleId = (string) Str::uuid();
    DB::table('automation_rules')->insert([
        'id' => $ruleId,
        'organization_id' => $this->orgId,
        'name' => 'Rule',
        'priority' => 1,
        'action_type' => 'reply_fixed',
        'delay_seconds' => 120,
        'daily_limit' => 100,
        'is_active' => true,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson("/api/v1/automation-rules/{$ruleId}/executions", $this->headers);

    $response->assertStatus(200);
});

it('returns 422 on priority conflict', function () {
    // Create first rule
    $this->postJson('/api/v1/automation-rules', [
        'name' => 'First',
        'priority' => 1,
        'conditions' => [
            ['field' => 'keyword', 'operator' => 'contains', 'value' => 'test'],
        ],
        'action_type' => 'reply_fixed',
        'response_template' => 'Reply',
    ], $this->headers)->assertStatus(201);

    // Try to create second with same priority
    $response = $this->postJson('/api/v1/automation-rules', [
        'name' => 'Second',
        'priority' => 1,
        'conditions' => [
            ['field' => 'keyword', 'operator' => 'contains', 'value' => 'hello'],
        ],
        'action_type' => 'reply_fixed',
        'response_template' => 'Reply',
    ], $this->headers);

    $response->assertStatus(422);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/automation-rules');

    $response->assertStatus(401);
});
