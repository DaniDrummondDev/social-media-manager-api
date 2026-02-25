<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Create a client for cost allocation tests
    $this->clientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Cost Allocation Client',
        'email' => 'cost-client@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

it('creates a cost allocation with 201', function () {
    $response = $this->postJson('/api/v1/cost-allocations', [
        'client_id' => $this->clientId,
        'resource_type' => 'campaign',
        'description' => 'Instagram Ads campaign cost',
        'cost_cents' => 150000,
        'currency' => 'BRL',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'client_id',
                    'resource_type',
                    'description',
                    'cost_cents',
                    'currency',
                    'allocated_at',
                    'created_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('cost_allocation');
    expect($response->json('data.attributes.client_id'))->toBe($this->clientId);
    expect($response->json('data.attributes.cost_cents'))->toBe(150000);
    expect($response->json('data.attributes.resource_type'))->toBe('campaign');
});

it('lists cost allocations with 200', function () {
    $now = now()->toDateTimeString();

    DB::table('cost_allocations')->insert([
        'id' => Str::uuid()->toString(),
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'resource_type' => 'ai_generation',
        'description' => 'AI content generation',
        'cost_cents' => 5000,
        'currency' => 'BRL',
        'allocated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('cost_allocations')->insert([
        'id' => Str::uuid()->toString(),
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'resource_type' => 'media_storage',
        'description' => 'Media storage for February',
        'cost_cents' => 2500,
        'currency' => 'BRL',
        'allocated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson('/api/v1/cost-allocations', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'client_id',
                        'resource_type',
                        'description',
                        'cost_cents',
                        'currency',
                        'allocated_at',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/cost-allocations');

    $response->assertStatus(401);
});
