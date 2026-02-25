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

it('returns usage 200 with percentages', function () {
    $response = $this->getJson('/api/v1/billing/usage', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'plan',
                    'billing_cycle',
                    'current_period_end',
                    'usage',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('usage');
    expect($response->json('data.attributes.plan'))->toBe('Free');
    expect($response->json('data.attributes.billing_cycle'))->toBe('monthly');
});

it('returns usage with resource types including used and limit', function () {
    $response = $this->getJson('/api/v1/billing/usage', $this->headers);

    $response->assertStatus(200);

    $usage = $response->json('data.attributes.usage');

    expect($usage)->toHaveKey('publications');
    expect($usage['publications'])->toHaveKeys(['used', 'limit', 'percentage']);
    expect($usage['publications']['used'])->toBe(0);
    expect($usage['publications']['limit'])->toBe(30);
    expect($usage['publications']['percentage'])->toEqual(0);
});
