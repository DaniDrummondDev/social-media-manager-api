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

it('gets dashboard with 200', function () {
    $response = $this->getJson('/api/v1/listening/dashboard', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'total_mentions',
                'sentiment_breakdown',
                'mentions_trend',
                'top_authors',
                'top_keywords',
                'platform_breakdown',
                'period',
            ],
        ]);

    expect($response->json('data.total_mentions'))->toBeInt();
    expect($response->json('data.period'))->toBeString();
});

it('gets dashboard with period filter', function () {
    $response = $this->getJson('/api/v1/listening/dashboard?period=30d', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'total_mentions',
                'sentiment_breakdown',
                'mentions_trend',
                'platform_breakdown',
                'period',
            ],
        ]);
});

it('gets sentiment trend with 200', function () {
    $response = $this->getJson('/api/v1/listening/dashboard/sentiment-trend', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
        ]);

    // Data is an array of trend items (may be empty if no mentions)
    expect($response->json('data'))->toBeArray();
});

it('gets platform breakdown with 200', function () {
    $response = $this->getJson('/api/v1/listening/dashboard/platform-breakdown', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
        ]);

    // Data is an array of platform breakdown items (may be empty if no mentions)
    expect($response->json('data'))->toBeArray();
});

it('returns 401 without auth for dashboard', function () {
    $response = $this->getJson('/api/v1/listening/dashboard');

    $response->assertStatus(401);
});

it('returns 401 without auth for sentiment trend', function () {
    $response = $this->getJson('/api/v1/listening/dashboard/sentiment-trend');

    $response->assertStatus(401);
});

it('returns 401 without auth for platform breakdown', function () {
    $response = $this->getJson('/api/v1/listening/dashboard/platform-breakdown');

    $response->assertStatus(401);
});
