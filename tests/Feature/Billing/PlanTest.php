<?php

declare(strict_types=1);

use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

it('lists all plans 200 without auth', function () {
    $response = $this->getJson('/api/v1/plans');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBe(4);
});

it('returns plans with correct structure', function () {
    $response = $this->getJson('/api/v1/plans');

    $response->assertStatus(200);

    $plans = $response->json('data');
    $first = $plans[0]['attributes'];

    expect($first)->toHaveKeys([
        'name',
        'slug',
        'description',
        'price_monthly_cents',
        'price_yearly_cents',
        'currency',
        'limits',
        'features',
    ]);
});

it('returns plans sorted by sort_order', function () {
    $response = $this->getJson('/api/v1/plans');

    $response->assertStatus(200);

    $plans = $response->json('data');
    $names = array_map(fn ($p) => $p['attributes']['name'], $plans);

    expect($names)->toBe(['Free', 'Creator', 'Professional', 'Agency']);
});
