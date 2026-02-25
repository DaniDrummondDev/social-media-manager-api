<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);
});

it('returns 202 when export is created', function () {
    $response = $this->postJson('/api/v1/analytics/exports', [
        'type' => 'overview',
        'format' => 'pdf',
        'period' => '30d',
    ], $this->headers);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'report_type',
                    'format',
                    'status',
                    'created_at',
                ],
            ],
        ])
        ->assertJsonPath('data.attributes.status', 'processing')
        ->assertJsonPath('data.attributes.report_type', 'overview');
});

it('returns 422 on invalid type', function () {
    $response = $this->postJson('/api/v1/analytics/exports', [
        'type' => 'invalid',
        'format' => 'pdf',
    ], $this->headers);

    $response->assertStatus(422);
});

it('returns 422 on invalid format', function () {
    $response = $this->postJson('/api/v1/analytics/exports', [
        'type' => 'overview',
        'format' => 'xlsx',
    ], $this->headers);

    $response->assertStatus(422);
});

it('returns 422 when rate limit exceeded', function () {
    // Create 5 exports to reach limit
    for ($i = 0; $i < 5; $i++) {
        DB::table('report_exports')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->orgId,
            'user_id' => $this->user['id'],
            'type' => 'overview',
            'format' => 'pdf',
            'status' => 'processing',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    $response = $this->postJson('/api/v1/analytics/exports', [
        'type' => 'overview',
        'format' => 'pdf',
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'EXPORT_RATE_LIMIT_EXCEEDED');
});

it('lists exports', function () {
    DB::table('report_exports')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'user_id' => $this->user['id'],
        'type' => 'overview',
        'format' => 'pdf',
        'status' => 'processing',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/analytics/exports', $this->headers);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('shows a single export', function () {
    $exportId = (string) Str::uuid();

    DB::table('report_exports')->insert([
        'id' => $exportId,
        'organization_id' => $this->orgId,
        'user_id' => $this->user['id'],
        'type' => 'network',
        'format' => 'csv',
        'status' => 'ready',
        'file_path' => '/reports/test.csv',
        'file_size' => 2048,
        'expires_at' => now()->addDay()->toDateTimeString(),
        'completed_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson("/api/v1/analytics/exports/{$exportId}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $exportId)
        ->assertJsonPath('data.attributes.report_type', 'network')
        ->assertJsonPath('data.attributes.status', 'ready');
});

it('returns 422 when export not found', function () {
    $fakeId = (string) Str::uuid();
    $response = $this->getJson("/api/v1/analytics/exports/{$fakeId}", $this->headers);

    $response->assertStatus(422);
});
