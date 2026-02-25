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

    // Create active subscription on Free plan
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

it('returns subscription 200 with auth', function () {
    $response = $this->getJson('/api/v1/billing/subscription', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'plan',
                    'status',
                    'billing_cycle',
                    'current_period_start',
                    'current_period_end',
                    'cancel_at_period_end',
                    'created_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('subscription');
    expect($response->json('data.attributes.status'))->toBe('active');
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/billing/subscription');

    $response->assertStatus(401);
});
