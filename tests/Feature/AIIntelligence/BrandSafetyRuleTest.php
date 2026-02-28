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
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // AI Intelligence requires Professional plan
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

function insertSafetyRule(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('brand_safety_rules')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'rule_type' => 'blocked_word',
        'rule_config' => json_encode(['words' => ['spam', 'offensive']]),
        'severity' => 'warning',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ], $overrides));

    return $id;
}

it('POST /brand-safety/rules — 201 creates rule', function () {
    $response = $this->postJson('/api/v1/brand-safety/rules', [
        'rule_type' => 'blocked_word',
        'rule_config' => ['words' => ['spam', 'offensive']],
        'severity' => 'warning',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'rule_type',
                    'rule_config',
                    'severity',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('safety_rule')
        ->and($response->json('data.attributes.rule_type'))->toBe('blocked_word')
        ->and($response->json('data.attributes.is_active'))->toBeTrue();
});

it('GET /brand-safety/rules — 200 lists rules', function () {
    insertSafetyRule($this->orgId);
    insertSafetyRule($this->orgId);

    $response = $this->getJson('/api/v1/brand-safety/rules', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'rule_type',
                        'rule_config',
                        'severity',
                        'is_active',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('PUT /brand-safety/rules/{id} — 200 updates rule', function () {
    $ruleId = insertSafetyRule($this->orgId);

    $response = $this->putJson("/api/v1/brand-safety/rules/{$ruleId}", [
        'severity' => 'block',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'rule_type',
                    'severity',
                ],
            ],
        ]);

    expect($response->json('data.attributes.severity'))->toBe('block');
});

it('DELETE /brand-safety/rules/{id} — 204 deletes rule', function () {
    $ruleId = insertSafetyRule($this->orgId);

    $response = $this->deleteJson("/api/v1/brand-safety/rules/{$ruleId}", [], $this->headers);

    $response->assertStatus(204);
});

it('POST /brand-safety/rules — 422 validation error missing rule_type', function () {
    $response = $this->postJson('/api/v1/brand-safety/rules', [
        'rule_config' => ['words' => ['spam']],
        'severity' => 'warning',
    ], $this->headers);

    $response->assertStatus(422);
});

it('GET /brand-safety/rules — 401 unauthenticated', function () {
    $response = $this->getJson('/api/v1/brand-safety/rules');

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org rules', function () {
    // Insert rule for own org
    $ownRuleId = insertSafetyRule($this->orgId);

    // Create another org with a different user
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    $otherRuleId = insertSafetyRule($otherOrgId);

    // List should only return own org rules
    $response = $this->getJson('/api/v1/brand-safety/rules', $this->headers);

    $response->assertStatus(200);

    $ids = array_column($response->json('data'), 'id');
    expect($ids)->toContain($ownRuleId)
        ->and($ids)->not->toContain($otherRuleId);
});

it('cannot update other org rule', function () {
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    $otherRuleId = insertSafetyRule($otherOrgId);

    $response = $this->putJson("/api/v1/brand-safety/rules/{$otherRuleId}", [
        'severity' => 'block',
    ], $this->headers);

    $response->assertStatus(422);
});

it('cannot delete other org rule', function () {
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    $otherRuleId = insertSafetyRule($otherOrgId);

    $response = $this->deleteJson("/api/v1/brand-safety/rules/{$otherRuleId}", [], $this->headers);

    $response->assertStatus(422);
});
