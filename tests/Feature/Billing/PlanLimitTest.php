<?php

declare(strict_types=1);

use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
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
    $now = now();
    DB::table('subscriptions')->insert([
        'id' => $this->subscriptionId,
        'organization_id' => $this->orgId,
        'plan_id' => PlanSeeder::FREE_PLAN_ID,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => $now->copy()->startOfMonth()->toDateTimeString(),
        'current_period_end' => $now->copy()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => $now->toDateTimeString(),
        'updated_at' => $now->toDateTimeString(),
    ]);

    // Register a test route with the plan.limit middleware
    Route::middleware(['api', 'auth.jwt', 'org.context', 'plan.limit:publications'])
        ->prefix('api/v1')
        ->get('/_test-plan-limit', fn () => response()->json(['ok' => true]));
});

it('returns 402 when plan limit is exceeded', function () {
    // Free plan allows 30 publications/month — insert usage record at the limit
    $periodStart = now()->startOfMonth()->format('Y-m-d');
    $periodEnd = now()->endOfMonth()->format('Y-m-d');

    DB::table('usage_records')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'resource_type' => 'publications',
        'quantity' => 30,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'recorded_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/_test-plan-limit', $this->headers);

    $response->assertStatus(402);
    expect($response->json('errors.0.code'))->toBe('PLAN_LIMIT_REACHED');
});

it('passes when within plan limit', function () {
    // Free plan allows 30 publications/month — insert usage record below limit
    $periodStart = now()->startOfMonth()->format('Y-m-d');
    $periodEnd = now()->endOfMonth()->format('Y-m-d');

    DB::table('usage_records')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'resource_type' => 'publications',
        'quantity' => 10,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'recorded_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/_test-plan-limit', $this->headers);

    $response->assertStatus(200);
    expect($response->json('ok'))->toBeTrue();
});

it('passes for unlimited resources', function () {
    // Switch subscription to Agency plan which has unlimited publications (-1)
    DB::table('subscriptions')
        ->where('id', $this->subscriptionId)
        ->update(['plan_id' => PlanSeeder::AGENCY_PLAN_ID]);

    // Insert high usage that would exceed any finite limit
    $periodStart = now()->startOfMonth()->format('Y-m-d');
    $periodEnd = now()->endOfMonth()->format('Y-m-d');

    DB::table('usage_records')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'resource_type' => 'publications',
        'quantity' => 99999,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'recorded_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/_test-plan-limit', $this->headers);

    $response->assertStatus(200);
    expect($response->json('ok'))->toBeTrue();
});
