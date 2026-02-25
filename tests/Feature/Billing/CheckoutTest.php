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
});

it('returns 403 for non-owner (member role)', function () {
    $headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email'], 'member');

    $response = $this->postJson('/api/v1/billing/checkout', [
        'plan_slug' => 'creator',
        'billing_cycle' => 'monthly',
        'success_url' => 'https://app.example.com/success',
        'cancel_url' => 'https://app.example.com/cancel',
    ], $headers);

    $response->assertStatus(403);
});

it('returns 422 for invalid plan_slug', function () {
    $headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    $response = $this->postJson('/api/v1/billing/checkout', [
        'plan_slug' => '',
        'billing_cycle' => 'invalid',
        'success_url' => 'not-a-url',
        'cancel_url' => 'not-a-url',
    ], $headers);

    $response->assertStatus(422);
});
