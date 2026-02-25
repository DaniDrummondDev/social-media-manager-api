<?php

declare(strict_types=1);

use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);
});

it('returns 200 with overview data', function () {
    $response = $this->getJson('/api/v1/analytics/overview?period=30d', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'period',
                'summary',
                'comparison',
                'by_network',
                'trend',
                'top_contents',
            ],
        ]);
});

it('returns 200 with 7d period', function () {
    $response = $this->getJson('/api/v1/analytics/overview?period=7d', $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.period', '7d');
});

it('returns 422 when custom period without from/to', function () {
    $response = $this->getJson('/api/v1/analytics/overview?period=custom', $this->headers);

    $response->assertStatus(422);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/analytics/overview?period=30d');

    $response->assertStatus(401);
});
