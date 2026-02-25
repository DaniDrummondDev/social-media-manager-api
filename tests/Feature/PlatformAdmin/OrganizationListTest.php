<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns 200 with list of organizations for admin', function () {
    $auth = createAdminAndGetToken('admin');

    // Create additional organizations to list
    for ($i = 0; $i < 3; $i++) {
        $extraOrgId = (string) \Illuminate\Support\Str::uuid();
        DB::table('organizations')->insert([
            'id' => $extraOrgId,
            'name' => "Extra Org {$i}",
            'slug' => "extra-org-{$extraOrgId}",
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $response = $this->getJson('/api/v1/admin/organizations', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ],
            'meta',
        ]);

    // Admin's own org + 3 extra orgs = at least 4
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(4);
});

it('supports cursor-based pagination', function () {
    $auth = createAdminAndGetToken('super_admin');

    // Create additional organizations
    for ($i = 0; $i < 5; $i++) {
        $extraOrgId = (string) \Illuminate\Support\Str::uuid();
        DB::table('organizations')->insert([
            'id' => $extraOrgId,
            'name' => "Paginated Org {$i}",
            'slug' => "paginated-org-{$extraOrgId}",
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $response = $this->getJson('/api/v1/admin/organizations?per_page=2', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);

    expect(count($response->json('data')))->toBe(2);
    expect($response->json('meta.has_more'))->toBeTrue();
    expect($response->json('meta.next_cursor'))->not->toBeNull();

    // Fetch next page using cursor
    $cursor = $response->json('meta.next_cursor');
    $nextResponse = $this->getJson("/api/v1/admin/organizations?per_page=2&cursor={$cursor}", [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $nextResponse->assertStatus(200);
    expect(count($nextResponse->json('data')))->toBeGreaterThanOrEqual(1);
});

it('supports status filter', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/organizations?status=active', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/admin/organizations')->assertStatus(401);
});

it('returns 403 for regular user', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/organizations', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});
