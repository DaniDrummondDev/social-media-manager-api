<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createAdditionalUsers(int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        $userId = (string) \Illuminate\Support\Str::uuid();
        DB::table('users')->insert([
            'id' => $userId,
            'name' => "Extra User {$i}",
            'email' => "extra-user-{$userId}@test.com",
            'password' => 'hashed',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

it('returns 200 with list of users for admin', function () {
    $auth = createAdminAndGetToken('admin');
    createAdditionalUsers(3);

    $response = $this->getJson('/api/v1/admin/users', [
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

    // Admin user + 3 extra users = at least 4
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(4);
});

it('returns 200 with list of users for super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->getJson('/api/v1/admin/users', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'meta',
        ]);
});

it('returns 200 with list of users for support', function () {
    $auth = createAdminAndGetToken('support');

    $response = $this->getJson('/api/v1/admin/users', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
});

it('supports cursor-based pagination', function () {
    $auth = createAdminAndGetToken('super_admin');
    createAdditionalUsers(5);

    $response = $this->getJson('/api/v1/admin/users?per_page=2', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(2);
    expect($response->json('meta.has_more'))->toBeTrue();
    expect($response->json('meta.next_cursor'))->not->toBeNull();

    // Fetch next page using cursor
    $cursor = $response->json('meta.next_cursor');
    $nextResponse = $this->getJson("/api/v1/admin/users?per_page=2&cursor={$cursor}", [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $nextResponse->assertStatus(200);
    expect(count($nextResponse->json('data')))->toBeGreaterThanOrEqual(1);
});

it('supports filtering by status', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/users?status=active', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
});

it('supports search filter', function () {
    $auth = createAdminAndGetToken('admin');

    // Create a user with a distinctive name
    $userId = (string) \Illuminate\Support\Str::uuid();
    DB::table('users')->insert([
        'id' => $userId,
        'name' => 'Unique Searchable Name',
        'email' => "searchable-{$userId}@test.com",
        'password' => 'hashed',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/admin/users?search=Unique+Searchable', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
});

it('supports sort parameter', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/users?sort=-created_at', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/admin/users')->assertStatus(401);
});

it('returns 403 for regular user', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/users', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});
