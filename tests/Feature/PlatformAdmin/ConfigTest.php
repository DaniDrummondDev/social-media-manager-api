<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use Database\Seeders\SystemConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemConfigSeeder::class);
});

function createAdminAndGetToken(string $role = 'super_admin'): array
{
    $userId = (string) \Illuminate\Support\Str::uuid();
    $adminId = (string) \Illuminate\Support\Str::uuid();
    $orgId = (string) \Illuminate\Support\Str::uuid();

    DB::table('users')->insert([
        'id' => $userId,
        'name' => 'Test Admin',
        'email' => "admin-{$userId}@test.com",
        'password' => 'hashed',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('organizations')->insert([
        'id' => $orgId,
        'name' => 'Test Org',
        'slug' => "test-org-{$orgId}",
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('organization_members')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'organization_id' => $orgId,
        'user_id' => $userId,
        'role' => 'owner',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('platform_admins')->insert([
        'id' => $adminId,
        'user_id' => $userId,
        'role' => $role,
        'permissions' => json_encode([]),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tokenService = app(AuthTokenServiceInterface::class);
    $result = $tokenService->generateAccessToken($userId, $orgId, "admin-{$userId}@test.com", 'owner');

    return ['token' => $result['token'], 'userId' => $userId, 'adminId' => $adminId, 'orgId' => $orgId];
}

function createRegularUserToken(): array
{
    $userId = (string) \Illuminate\Support\Str::uuid();
    $orgId = (string) \Illuminate\Support\Str::uuid();

    DB::table('users')->insert([
        'id' => $userId, 'name' => 'Regular User', 'email' => "user-{$userId}@test.com",
        'password' => 'hashed', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('organizations')->insert([
        'id' => $orgId, 'name' => 'User Org', 'slug' => "user-org-{$orgId}",
        'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('organization_members')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(), 'organization_id' => $orgId,
        'user_id' => $userId, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $tokenService = app(AuthTokenServiceInterface::class);
    $result = $tokenService->generateAccessToken($userId, $orgId, "user-{$userId}@test.com", 'owner');

    return ['token' => $result['token'], 'userId' => $userId, 'orgId' => $orgId];
}

it('returns system configs for admin', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/config', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(6);
});

it('returns system configs for super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->getJson('/api/v1/admin/config', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(6);
});

it('returns 403 for support role trying to view config', function () {
    $auth = createAdminAndGetToken('support');

    $response = $this->getJson('/api/v1/admin/config', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('updates config as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->patchJson('/api/v1/admin/config', [
        'configs' => [
            ['key' => 'default_trial_days', 'value' => 7],
        ],
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('updates multiple configs as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->patchJson('/api/v1/admin/config', [
        'configs' => [
            ['key' => 'default_trial_days', 'value' => 7],
            ['key' => 'maintenance_mode', 'value' => true],
        ],
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 403 for admin role trying to update config', function () {
    $auth = createAdminAndGetToken('admin');

    $this->patchJson('/api/v1/admin/config', [
        'configs' => [
            ['key' => 'maintenance_mode', 'value' => true],
        ],
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for support role trying to update config', function () {
    $auth = createAdminAndGetToken('support');

    $this->patchJson('/api/v1/admin/config', [
        'configs' => [
            ['key' => 'maintenance_mode', 'value' => true],
        ],
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 422 when configs array is empty', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->patchJson('/api/v1/admin/config', [
        'configs' => [],
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/admin/config')->assertStatus(401);
});

it('returns 403 for regular user trying to view config', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/config', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});
