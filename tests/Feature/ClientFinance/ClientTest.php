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
});

it('creates a client with 201', function () {
    $response = $this->postJson('/api/v1/clients', [
        'name' => 'Acme Corp',
        'email' => 'contact@acme.com',
        'phone' => '+5511999999999',
        'company_name' => 'Acme Corporation LTDA',
        'tax_id' => '11222333000181',
        'notes' => 'Important client',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'phone',
                    'company_name',
                    'tax_id',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('client');
    expect($response->json('data.attributes.name'))->toBe('Acme Corp');
    expect($response->json('data.attributes.status'))->toBe('active');
});

it('lists clients with 200', function () {
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'name' => 'Client Alpha',
        'email' => 'alpha@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('clients')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'name' => 'Client Beta',
        'email' => 'beta@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson('/api/v1/clients', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'status',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('shows a client with 200', function () {
    $clientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $clientId,
        'organization_id' => $this->orgId,
        'name' => 'Client Detail',
        'email' => 'detail@example.com',
        'company_name' => 'Detail Co',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson("/api/v1/clients/{$clientId}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'company_name',
                    'status',
                ],
            ],
        ]);

    expect($response->json('data.id'))->toBe($clientId);
    expect($response->json('data.attributes.name'))->toBe('Client Detail');
});

it('updates a client with 200', function () {
    $clientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $clientId,
        'organization_id' => $this->orgId,
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->patchJson("/api/v1/clients/{$clientId}", [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'status',
                ],
            ],
        ]);

    expect($response->json('data.attributes.name'))->toBe('New Name');
    expect($response->json('data.attributes.email'))->toBe('new@example.com');
});

it('archives a client with 200', function () {
    $clientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $clientId,
        'organization_id' => $this->orgId,
        'name' => 'To Archive',
        'email' => 'archive@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/clients/{$clientId}/archive", [], $this->headers);

    $response->assertStatus(200);

    expect($response->json('data.archived'))->toBeTrue();
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/clients');

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org clients', function () {
    // Create a client belonging to THIS org
    $ownClientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $ownClientId,
        'organization_id' => $this->orgId,
        'name' => 'Own Client',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Create another org with a different user
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];

    $otherClientId = Str::uuid()->toString();

    DB::table('clients')->insert([
        'id' => $otherClientId,
        'organization_id' => $otherOrgId,
        'name' => 'Other Org Client',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // List should only return own org clients
    $listResponse = $this->getJson('/api/v1/clients', $this->headers);

    $listResponse->assertStatus(200);

    $ids = array_column($listResponse->json('data'), 'id');
    expect($ids)->toContain($ownClientId);
    expect($ids)->not->toContain($otherClientId);

    // Show other org's client should return 422 (CLIENT_NOT_FOUND)
    $showResponse = $this->getJson("/api/v1/clients/{$otherClientId}", $this->headers);

    $showResponse->assertStatus(422);
});
