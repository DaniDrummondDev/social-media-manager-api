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

function insertRecommendation(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('posting_time_recommendations')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'social_account_id' => null,
        'provider' => 'instagram',
        'heatmap' => json_encode([
            ['day' => 1, 'hour' => 9, 'score' => 85],
            ['day' => 3, 'hour' => 14, 'score' => 72],
        ]),
        'top_slots' => json_encode([
            ['day' => 1, 'day_name' => 'Monday', 'hour' => 9, 'avg_engagement_rate' => 4.5, 'sample_size' => 30],
        ]),
        'worst_slots' => json_encode([
            ['day' => 0, 'day_name' => 'Sunday', 'hour' => 3, 'avg_engagement_rate' => 0.2, 'sample_size' => 30],
        ]),
        'sample_size' => 60,
        'confidence_level' => 'high',
        'calculated_at' => $now,
        'expires_at' => now()->addDays(7)->toDateTimeString(),
        'created_at' => $now,
    ], $overrides));

    return $id;
}

it('GET /ai-intelligence/best-times — 200 with data', function () {
    insertRecommendation($this->orgId);

    $response = $this->getJson('/api/v1/ai-intelligence/best-times?provider=instagram', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'top_slots',
                'worst_slots',
                'confidence_level',
                'sample_size',
                'calculated_at',
                'expires_at',
            ],
        ]);

    expect($response->json('data.confidence_level'))->toBe('high')
        ->and($response->json('data.sample_size'))->toBe(60);
});

it('GET /ai-intelligence/best-times — 200 with null data when no recommendation', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/best-times', $this->headers);

    $response->assertStatus(200);

    expect($response->json('data'))->toBeNull();
});

it('GET /ai-intelligence/best-times/heatmap — 200 with heatmap data', function () {
    insertRecommendation($this->orgId);

    $response = $this->getJson('/api/v1/ai-intelligence/best-times/heatmap?provider=instagram', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'heatmap',
                'provider',
                'confidence_level',
                'sample_size',
                'calculated_at',
            ],
        ]);

    expect($response->json('data.heatmap'))->toHaveCount(2)
        ->and($response->json('data.provider'))->toBe('instagram');
});

it('POST /ai-intelligence/best-times/recalculate — 202', function () {
    Bus::fake();

    $response = $this->postJson('/api/v1/ai-intelligence/best-times/recalculate', [
        'provider' => 'instagram',
    ], $this->headers);

    $response->assertStatus(202);

    expect($response->json('data.message'))->toBeString();
});

it('GET /ai-intelligence/best-times — 401 unauthenticated', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/best-times');

    $response->assertStatus(401);
});
