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
});

it('returns 422 when canceling free plan', function () {
    $now = now();
    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
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

    $response = $this->postJson('/api/v1/billing/cancel', [], $this->headers);

    $response->assertStatus(422);
    expect($response->json('errors.0.code'))->toBe('CANNOT_CANCEL_FREE_PLAN');
});

it('returns 422 when reactivating subscription that is not canceling', function () {
    $now = now();
    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
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

    $response = $this->postJson('/api/v1/billing/reactivate', [], $this->headers);

    $response->assertStatus(422);
    expect($response->json('errors.0.code'))->toBe('INVALID_SUBSCRIPTION_TRANSITION');
});

it('returns 403 for non-owner on cancel', function () {
    $now = now();
    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
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

    $memberHeaders = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email'], 'member');

    $response = $this->postJson('/api/v1/billing/cancel', [], $memberHeaders);

    $response->assertStatus(403);
});
