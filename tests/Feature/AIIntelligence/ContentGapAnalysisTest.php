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

function insertGapAnalysis(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('content_gap_analyses')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'competitor_query_ids' => json_encode([Str::uuid()->toString()]),
        'analysis_period_start' => now()->subDays(30)->toDateTimeString(),
        'analysis_period_end' => $now,
        'our_topics' => json_encode([['topic' => 'Tech', 'frequency' => 10, 'avg_engagement' => 4.5]]),
        'competitor_topics' => json_encode([['topic' => 'AI', 'source_competitor' => 'rival', 'frequency' => 20, 'avg_engagement' => 6.0]]),
        'gaps' => json_encode([['topic' => 'AI', 'opportunity_score' => 85, 'competitor_count' => 3, 'recommendation' => 'Create AI content']]),
        'opportunities' => json_encode([['topic' => 'AI', 'reason' => 'High demand', 'suggested_content_type' => 'tutorial', 'estimated_impact' => 'high']]),
        'status' => 'generated',
        'generated_at' => $now,
        'expires_at' => now()->addDays(7)->toDateTimeString(),
        'created_at' => $now,
    ], $overrides));

    return $id;
}

it('POST /gap-analysis/generate — 202 with analysis_id', function () {
    Bus::fake();

    $response = $this->postJson('/api/v1/ai-intelligence/gap-analysis/generate', [
        'competitor_query_ids' => [Str::uuid()->toString(), Str::uuid()->toString()],
        'period_days' => 30,
    ], $this->headers);

    $response->assertStatus(202)
        ->assertJsonStructure(['data' => ['analysis_id', 'status', 'message']]);

    expect($response->json('data.status'))->toBe('generating');
});

it('POST /gap-analysis/generate — 422 without competitor_query_ids', function () {
    $response = $this->postJson('/api/v1/ai-intelligence/gap-analysis/generate', [], $this->headers);

    $response->assertStatus(422);
});

it('POST /gap-analysis/generate — 422 with invalid period_days', function () {
    $response = $this->postJson('/api/v1/ai-intelligence/gap-analysis/generate', [
        'competitor_query_ids' => [Str::uuid()->toString()],
        'period_days' => 200,
    ], $this->headers);

    $response->assertStatus(422);
});

it('GET /gap-analyses — 200 with cursor-based pagination', function () {
    insertGapAnalysis($this->orgId);

    $response = $this->getJson('/api/v1/ai-intelligence/gap-analyses', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'type', 'attributes' => [
                'status', 'competitor_query_count', 'analysis_period_start',
                'analysis_period_end', 'gap_count', 'opportunity_count',
                'generated_at', 'expires_at',
            ]]],
            'meta' => ['next_cursor'],
        ]);

    expect(count($response->json('data')))->toBe(1);
});

it('GET /gap-analyses — 200 empty when no analyses', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/gap-analyses', $this->headers);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});

it('GET /gap-analyses/{id} — 200 with full detail', function () {
    $id = insertGapAnalysis($this->orgId);

    $response = $this->getJson("/api/v1/ai-intelligence/gap-analyses/{$id}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => [
            'id', 'type', 'attributes' => [
                'status', 'competitor_query_ids', 'analysis_period',
                'our_topics', 'competitor_topics', 'gaps', 'opportunities',
                'generated_at', 'expires_at',
            ],
        ]]);
});

it('GET /gap-analyses/{id} — 422 when not found', function () {
    $fakeId = Str::uuid()->toString();

    $response = $this->getJson("/api/v1/ai-intelligence/gap-analyses/{$fakeId}", $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'GAP_ANALYSIS_NOT_FOUND');
});

it('GET /gap-analyses/{id}/opportunities — 200 with actionable gaps', function () {
    $id = insertGapAnalysis($this->orgId);

    $response = $this->getJson("/api/v1/ai-intelligence/gap-analyses/{$id}/opportunities", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => [
            'opportunities', 'total_gaps', 'actionable_opportunities',
        ]]);

    expect($response->json('data.total_gaps'))->toBe(1)
        ->and($response->json('data.actionable_opportunities'))->toBe(1);
});

it('GET /gap-analyses — 401 unauthenticated', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/gap-analyses');

    $response->assertStatus(401);
});

it('isolates gap analyses by organization', function () {
    insertGapAnalysis($this->orgId);

    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    insertGapAnalysis($otherOrgId, ['id' => Str::uuid()->toString()]);

    $response = $this->getJson('/api/v1/ai-intelligence/gap-analyses', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});

it('GET /gap-analyses/{id} — 422 when belongs to other organization', function () {
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    $id = insertGapAnalysis($otherOrgId);

    $response = $this->getJson("/api/v1/ai-intelligence/gap-analyses/{$id}", $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'GAP_ANALYSIS_NOT_FOUND');
});
