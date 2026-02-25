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

    // Create a client for contract tests
    $this->clientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Contract Test Client',
        'email' => 'contract-client@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

it('creates a contract with 201', function () {
    $response = $this->postJson("/api/v1/clients/{$this->clientId}/contracts", [
        'name' => 'Monthly Social Media Management',
        'type' => 'fixed_monthly',
        'value_cents' => 500000,
        'currency' => 'BRL',
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-12-31',
        'social_account_ids' => [],
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'client_id',
                    'name',
                    'type',
                    'value_cents',
                    'currency',
                    'starts_at',
                    'ends_at',
                    'social_account_ids',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('contract');
    expect($response->json('data.attributes.name'))->toBe('Monthly Social Media Management');
    expect($response->json('data.attributes.status'))->toBe('active');
    expect($response->json('data.attributes.value_cents'))->toBe(500000);
});

it('lists contracts for a client with 200', function () {
    $now = now()->toDateTimeString();

    DB::table('client_contracts')->insert([
        'id' => Str::uuid()->toString(),
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Contract A',
        'type' => 'fixed_monthly',
        'value_cents' => 300000,
        'currency' => 'BRL',
        'starts_at' => '2026-01-01',
        'status' => 'active',
        'social_account_ids' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_contracts')->insert([
        'id' => Str::uuid()->toString(),
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Contract B',
        'type' => 'per_campaign',
        'value_cents' => 100000,
        'currency' => 'BRL',
        'starts_at' => '2026-02-01',
        'status' => 'active',
        'social_account_ids' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson("/api/v1/clients/{$this->clientId}/contracts", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'client_id',
                        'name',
                        'type',
                        'value_cents',
                        'status',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('updates a contract with 200', function () {
    $contractId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_contracts')->insert([
        'id' => $contractId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Old Contract Name',
        'type' => 'fixed_monthly',
        'value_cents' => 200000,
        'currency' => 'BRL',
        'starts_at' => '2026-01-01',
        'status' => 'active',
        'social_account_ids' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->patchJson("/api/v1/contracts/{$contractId}", [
        'name' => 'Updated Contract Name',
        'value_cents' => 350000,
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'value_cents',
                    'status',
                ],
            ],
        ]);

    expect($response->json('data.attributes.name'))->toBe('Updated Contract Name');
    expect($response->json('data.attributes.value_cents'))->toBe(350000);
});

it('pauses a contract with 200', function () {
    $contractId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_contracts')->insert([
        'id' => $contractId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Active Contract',
        'type' => 'fixed_monthly',
        'value_cents' => 200000,
        'currency' => 'BRL',
        'starts_at' => '2026-01-01',
        'status' => 'active',
        'social_account_ids' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/contracts/{$contractId}/pause", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'status',
                ],
            ],
        ]);

    expect($response->json('data.attributes.status'))->toBe('paused');
});

it('completes a contract with 200', function () {
    $contractId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_contracts')->insert([
        'id' => $contractId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Contract to Complete',
        'type' => 'fixed_monthly',
        'value_cents' => 200000,
        'currency' => 'BRL',
        'starts_at' => '2026-01-01',
        'status' => 'active',
        'social_account_ids' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/contracts/{$contractId}/complete", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'status',
                ],
            ],
        ]);

    expect($response->json('data.attributes.status'))->toBe('completed');
});
