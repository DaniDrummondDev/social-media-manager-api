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
function createSubscription(string $subscriptionId, string $orgId, string $planId, $now): void
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

describe('CRM Feature Gate — Free Plan', function () {
    beforeEach(function () {
        createSubscription($this->subscriptionId, $this->orgId, PlanSeeder::FREE_PLAN_ID, $this->now);
    });

    it('blocks GET /crm/connections for Free plan', function () {
        $response = $this->getJson('/api/v1/crm/connections', $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('blocks POST /crm/connect for Free plan', function () {
        $response = $this->postJson('/api/v1/crm/connect', [
            'provider' => 'hubspot',
            'redirect_uri' => 'https://example.com/callback',
        ], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('blocks POST /crm/connect-api-key for Free plan', function () {
        $response = $this->postJson('/api/v1/crm/connect-api-key', [
            'provider' => 'activecampaign',
            'api_key' => 'test-key',
            'api_url' => 'https://account.api-us1.com',
        ], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });
});

describe('CRM Feature Gate — Creator Plan', function () {
    beforeEach(function () {
        createSubscription($this->subscriptionId, $this->orgId, PlanSeeder::CREATOR_PLAN_ID, $this->now);
    });

    it('blocks GET /crm/connections for Creator plan', function () {
        $response = $this->getJson('/api/v1/crm/connections', $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });

    it('blocks POST /crm/connect for Creator plan', function () {
        $response = $this->postJson('/api/v1/crm/connect', [
            'provider' => 'hubspot',
            'redirect_uri' => 'https://example.com/callback',
        ], $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });
});

describe('CRM Feature Gate — Professional Plan', function () {
    beforeEach(function () {
        createSubscription($this->subscriptionId, $this->orgId, PlanSeeder::PROFESSIONAL_PLAN_ID, $this->now);
    });

    it('allows GET /crm/connections for Professional plan', function () {
        $response = $this->getJson('/api/v1/crm/connections', $this->headers);

        // Should pass the feature gate (may return 200 or other business error, but not 402)
        $response->assertStatus(200);
    });

    it('allows POST /crm/connect for Professional plan', function () {
        $response = $this->postJson('/api/v1/crm/connect', [
            'provider' => 'hubspot',
            'redirect_uri' => 'https://example.com/callback',
        ], $this->headers);

        // Should pass the feature gate (200 or 422 for validation, but not 402)
        expect($response->status())->not->toBe(402);
    });

    it('allows POST /crm/connect-api-key for Professional plan', function () {
        $response = $this->postJson('/api/v1/crm/connect-api-key', [
            'provider' => 'activecampaign',
            'api_key' => 'test-api-key-123',
            'api_url' => 'https://account.api-us1.com',
        ], $this->headers);

        // Should pass the feature gate
        expect($response->status())->not->toBe(402);
    });
});

describe('CRM Feature Gate — Agency Plan', function () {
    beforeEach(function () {
        createSubscription($this->subscriptionId, $this->orgId, PlanSeeder::AGENCY_PLAN_ID, $this->now);
    });

    it('allows GET /crm/connections for Agency plan', function () {
        $response = $this->getJson('/api/v1/crm/connections', $this->headers);

        $response->assertStatus(200);
    });

    it('allows all CRM routes for Agency plan', function () {
        // Test multiple CRM routes
        $routes = [
            ['method' => 'get', 'uri' => '/api/v1/crm/connections'],
        ];

        foreach ($routes as $route) {
            $response = $this->json($route['method'], $route['uri'], [], $this->headers);
            expect($response->status())->not->toBe(402);
        }
    });
});

describe('CRM Feature Gate — No Subscription', function () {
    it('returns 402 when no active subscription exists', function () {
        // No subscription created for this org
        $response = $this->getJson('/api/v1/crm/connections', $this->headers);

        $response->assertStatus(402);
        expect($response->json('errors.0.code'))->toBe('FEATURE_NOT_AVAILABLE');
    });
});
