<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns dashboard metrics for super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->getJson('/api/v1/admin/dashboard', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'overview',
                    'subscriptions',
                    'usage',
                    'health',
                ],
            ],
        ]);
});

it('returns dashboard metrics for admin role', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/dashboard', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'overview',
                    'subscriptions',
                    'usage',
                    'health',
                ],
            ],
        ]);
});

it('returns dashboard metrics for support role', function () {
    $auth = createAdminAndGetToken('support');

    $response = $this->getJson('/api/v1/admin/dashboard', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
});

it('returns 403 for regular user without platform_admin record', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/dashboard', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});
