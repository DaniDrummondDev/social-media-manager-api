<?php

declare(strict_types=1);

use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Bus;
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

function insertAudienceInsight(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('audience_insights')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'social_account_id' => null,
        'insight_type' => 'preferred_topics',
        'insight_data' => json_encode(['topics' => [['name' => 'Tech', 'score' => 0.85, 'comment_count' => 120]]]),
        'source_comment_count' => 200,
        'period_start' => now()->subDays(30)->toDateTimeString(),
        'period_end' => $now,
        'confidence_score' => 0.85,
        'generated_at' => $now,
        'expires_at' => now()->addDays(7)->toDateTimeString(),
        'created_at' => $now,
    ], $overrides));

    return $id;
}

it('GET /audience-insights — 200 with insights array', function () {
    insertAudienceInsight($this->orgId);

    $response = $this->getJson('/api/v1/ai-intelligence/audience-insights', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'type', 'attributes' => [
                'insight_type', 'insight_data', 'source_comment_count',
                'confidence_score', 'period_start', 'period_end',
                'generated_at', 'expires_at',
            ]]],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('GET /audience-insights — 200 empty when no insights', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/audience-insights', $this->headers);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});

it('GET /audience-insights?type=sentiment_trends — filters by type', function () {
    insertAudienceInsight($this->orgId, ['insight_type' => 'preferred_topics']);
    insertAudienceInsight($this->orgId, [
        'id' => Str::uuid()->toString(),
        'insight_type' => 'sentiment_trends',
        'insight_data' => json_encode(['trend' => []]),
    ]);

    $response = $this->getJson('/api/v1/ai-intelligence/audience-insights?type=sentiment_trends', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1)
        ->and($response->json('data.0.attributes.insight_type'))->toBe('sentiment_trends');
});

it('POST /audience-insights/refresh — 202 queues job', function () {
    Bus::fake();

    $response = $this->postJson('/api/v1/ai-intelligence/audience-insights/refresh', [], $this->headers);

    $response->assertStatus(202)
        ->assertJsonStructure(['data' => ['message', 'estimated_completion']]);
});

it('GET /audience-insights — 401 unauthenticated', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/audience-insights');

    $response->assertStatus(401);
});

it('isolates insights by organization', function () {
    insertAudienceInsight($this->orgId);

    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    insertAudienceInsight($otherOrgId, ['id' => Str::uuid()->toString()]);

    $response = $this->getJson('/api/v1/ai-intelligence/audience-insights', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});

it('excludes expired insights', function () {
    insertAudienceInsight($this->orgId, [
        'expires_at' => now()->subDay()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/ai-intelligence/audience-insights', $this->headers);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});
