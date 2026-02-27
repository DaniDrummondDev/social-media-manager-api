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

    // Subscription with Professional plan (paid_advertising enabled)
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

function insertAdPerformanceInsight(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('ad_performance_insights')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'ad_insight_type' => 'best_audiences',
        'insight_data' => json_encode(['audiences' => [['audience_id' => 'a1', 'avg_ctr' => 1.5]]]),
        'sample_size' => 25,
        'confidence_level' => 'medium',
        'period_start' => now()->subDays(7)->toDateTimeString(),
        'period_end' => $now,
        'generated_at' => $now,
        'expires_at' => now()->addDays(7)->toDateTimeString(),
        'created_at' => $now,
        'updated_at' => $now,
    ], $overrides));

    return $id;
}

it('GET /ads/intelligence/insights — 200 with insights array', function () {
    insertAdPerformanceInsight($this->orgId);

    $response = $this->getJson('/api/v1/ads/intelligence/insights', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'type', 'attributes' => [
                'ad_insight_type', 'ad_insight_label', 'insight_data',
                'sample_size', 'confidence_level',
                'period_start', 'period_end', 'generated_at', 'expires_at',
            ]]],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('GET /ads/intelligence/insights — 200 empty when no insights', function () {
    $response = $this->getJson('/api/v1/ads/intelligence/insights', $this->headers);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});

it('GET /ads/intelligence/insights?type=best_audiences — filters by type', function () {
    insertAdPerformanceInsight($this->orgId, ['ad_insight_type' => 'best_audiences']);
    insertAdPerformanceInsight($this->orgId, [
        'id' => Str::uuid()->toString(),
        'ad_insight_type' => 'best_content_for_ads',
        'insight_data' => json_encode(['content_patterns' => []]),
    ]);

    $response = $this->getJson('/api/v1/ads/intelligence/insights?type=best_audiences', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1)
        ->and($response->json('data.0.attributes.ad_insight_type'))->toBe('best_audiences');
});

it('GET /ads/intelligence/insights — 401 unauthenticated', function () {
    $response = $this->getJson('/api/v1/ads/intelligence/insights');

    $response->assertStatus(401);
});

it('GET /ads/intelligence/insights — isolates by organization', function () {
    insertAdPerformanceInsight($this->orgId);

    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    insertAdPerformanceInsight($otherOrgId, ['id' => Str::uuid()->toString()]);

    $response = $this->getJson('/api/v1/ads/intelligence/insights', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});

it('GET /ads/intelligence/insights — excludes expired insights', function () {
    insertAdPerformanceInsight($this->orgId, [
        'expires_at' => now()->subDay()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/ads/intelligence/insights', $this->headers);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});

it('GET /ads/intelligence/targeting-suggestions — 200 with suggestions', function () {
    $contentId = Str::uuid()->toString();

    $response = $this->getJson(
        "/api/v1/ads/intelligence/targeting-suggestions?content_id={$contentId}",
        $this->headers,
    );

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'suggestions',
                'suggestion_count',
                'based_on_insight_type',
                'confidence_level',
            ],
        ]);
});

it('GET /ads/intelligence/targeting-suggestions — 401 unauthenticated', function () {
    $response = $this->getJson('/api/v1/ads/intelligence/targeting-suggestions?content_id=' . Str::uuid()->toString());

    $response->assertStatus(401);
});
