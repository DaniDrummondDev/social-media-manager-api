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
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    $this->subscriptionId = (string) Str::uuid();
    $this->now = now();
});

/**
 * Helper to create subscription with specific plan.
 */
function createAISubscription(string $subscriptionId, string $orgId, string $planId, $now): void
{
    DB::table('subscriptions')->insert([
        'id' => $subscriptionId,
        'organization_id' => $orgId,
        'plan_id' => $planId,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => $now->copy()->startOfMonth()->toDateTimeString(),
        'current_period_end' => $now->copy()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => $now->toDateTimeString(),
        'updated_at' => $now->toDateTimeString(),
    ]);
}

describe('AI Intelligence Feature Gate — Free Plan', function () {
    beforeEach(function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::FREE_PLAN_ID, $this->now);
    });

    it('blocks GET /ai-intelligence/best-times for Free plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/best-times', $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('blocks GET /ai-intelligence/content-profile for Free plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/content-profile', $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('blocks POST /ai-intelligence/gap-analysis/generate for Free plan', function () {
        $response = $this->postJson('/api/v1/ai-intelligence/gap-analysis/generate', [], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('blocks GET /ai-intelligence/audience-insights for Free plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/audience-insights', $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });
});

describe('AI Intelligence Feature Gate — Creator Plan', function () {
    beforeEach(function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::CREATOR_PLAN_ID, $this->now);
    });

    it('blocks all AI Intelligence routes for Creator plan', function () {
        $routes = [
            '/api/v1/ai-intelligence/best-times',
            '/api/v1/ai-intelligence/content-profile',
            '/api/v1/ai-intelligence/audience-insights',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route, $this->headers);
            $response->assertStatus(402);
            expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
        }
    });

    it('blocks AI Learning routes for Creator plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/prediction-accuracy', $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });
});

describe('AI Intelligence Feature Gate — Professional Plan', function () {
    beforeEach(function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::PROFESSIONAL_PLAN_ID, $this->now);
    });

    it('allows GET /ai-intelligence/best-times for Professional plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/best-times', $this->headers);

        // Should pass the feature gate (200 or 422, but not 402)
        expect($response->status())->not->toBe(402);
    });

    it('allows GET /ai-intelligence/content-profile for Professional plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/content-profile', $this->headers);

        expect($response->status())->not->toBe(402);
    });

    it('allows GET /ai-intelligence/audience-insights for Professional plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/audience-insights', $this->headers);

        expect($response->status())->not->toBe(402);
    });

    it('allows AI Learning routes for Professional plan', function () {
        $response = $this->getJson('/api/v1/ai-intelligence/prediction-accuracy', $this->headers);

        expect($response->status())->not->toBe(402);
    });
});

describe('AI Intelligence Feature Gate — Agency Plan', function () {
    beforeEach(function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::AGENCY_PLAN_ID, $this->now);
    });

    it('allows all AI Intelligence routes for Agency plan', function () {
        $routes = [
            '/api/v1/ai-intelligence/best-times',
            '/api/v1/ai-intelligence/content-profile',
            '/api/v1/ai-intelligence/audience-insights',
            '/api/v1/ai-intelligence/prediction-accuracy',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route, $this->headers);
            expect($response->status())->not->toBe(402);
        }
    });
});

describe('AI Generation Advanced Feature Gate', function () {
    it('blocks advanced AI routes for Free plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::FREE_PLAN_ID, $this->now);

        $response = $this->postJson('/api/v1/ai/adapt-content', [
            'content' => 'Test content',
            'target_network' => 'instagram',
        ], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('allows advanced AI routes for Creator plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::CREATOR_PLAN_ID, $this->now);

        $response = $this->postJson('/api/v1/ai/adapt-content', [
            'content' => 'Test content',
            'target_network' => 'instagram',
        ], $this->headers);

        // Should pass the feature gate
        expect($response->status())->not->toBe(402);
    });

    it('allows advanced AI routes for Professional plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::PROFESSIONAL_PLAN_ID, $this->now);

        $response = $this->postJson('/api/v1/ai/prompt-templates', [
            'name' => 'Test Template',
            'template' => 'Generate {{topic}}',
        ], $this->headers);

        // Should pass the feature gate
        expect($response->status())->not->toBe(402);
    });
});

describe('AI Learning Feature Gate', function () {
    it('blocks AI feedback for Free plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::FREE_PLAN_ID, $this->now);

        $response = $this->postJson('/api/v1/ai/feedback', [
            'generation_id' => (string) Str::uuid(),
            'rating' => 'positive',
        ], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('blocks AI feedback for Creator plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::CREATOR_PLAN_ID, $this->now);

        $response = $this->postJson('/api/v1/ai/feedback', [
            'generation_id' => (string) Str::uuid(),
            'rating' => 'positive',
        ], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('allows AI feedback for Professional plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::PROFESSIONAL_PLAN_ID, $this->now);

        $response = $this->postJson('/api/v1/ai/feedback', [
            'generation_id' => (string) Str::uuid(),
            'rating' => 'positive',
        ], $this->headers);

        // Should pass the feature gate
        expect($response->status())->not->toBe(402);
    });
});

describe('AI Generation Quota Limit', function () {
    it('blocks AI generation when quota exceeded for Free plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::FREE_PLAN_ID, $this->now);

        // Free plan: 50 ai_generations_month
        $periodStart = $this->now->copy()->startOfMonth()->format('Y-m-d');
        $periodEnd = $this->now->copy()->endOfMonth()->format('Y-m-d');

        DB::table('usage_records')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->orgId,
            'resource_type' => 'ai_generations',
            'quantity' => 50,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'recorded_at' => $this->now->toDateTimeString(),
        ]);

        $response = $this->postJson('/api/v1/ai/generate-title', [
            'topic' => 'Test topic',
            'tone' => 'professional',
        ], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('PLAN_LIMIT_REACHED');
    });

    it('allows AI generation when within quota for Free plan', function () {
        createAISubscription($this->subscriptionId, $this->orgId, PlanSeeder::FREE_PLAN_ID, $this->now);

        // Free plan: 50 ai_generations_month — use 10
        $periodStart = $this->now->copy()->startOfMonth()->format('Y-m-d');
        $periodEnd = $this->now->copy()->endOfMonth()->format('Y-m-d');

        DB::table('usage_records')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->orgId,
            'resource_type' => 'ai_generations',
            'quantity' => 10,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'recorded_at' => $this->now->toDateTimeString(),
        ]);

        $response = $this->postJson('/api/v1/ai/generate-title', [
            'topic' => 'Test topic',
            'tone' => 'professional',
        ], $this->headers);

        // Should pass the quota check (may fail validation, but not 402 for limit)
        expect($response->status())->not->toBe(402);
    });
});
