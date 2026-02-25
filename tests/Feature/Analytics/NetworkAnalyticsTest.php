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

    // Create a social account
    DB::table('social_accounts')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'connected_by' => $this->user['id'],
        'provider' => 'instagram',
        'provider_user_id' => 'ig-001',
        'username' => '@test_ig',
        'display_name' => 'Test Instagram',
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'token_expires_at' => now()->addDays(30)->toDateTimeString(),
        'scopes' => json_encode(['read']),
        'status' => 'connected',
        'connected_at' => now()->toDateTimeString(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('returns 200 with network analytics', function () {
    $response = $this->getJson('/api/v1/analytics/networks/instagram?period=30d', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'provider',
                'period',
                'account',
                'content_metrics',
                'comparison',
                'top_contents',
                'best_posting_times',
                'followers_trend',
            ],
        ]);
});

it('returns 200 with period filtering', function () {
    $response = $this->getJson('/api/v1/analytics/networks/instagram?period=7d', $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.period', '7d');
});

it('returns 422 on invalid provider', function () {
    $response = $this->getJson('/api/v1/analytics/networks/invalid_provider?period=30d', $this->headers);

    $response->assertStatus(422);
});
