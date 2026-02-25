<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

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

function createTargetOrganization(string $status = 'active'): string
{
    $orgId = (string) \Illuminate\Support\Str::uuid();

    DB::table('organizations')->insert([
        'id' => $orgId,
        'name' => 'Target Organization',
        'slug' => "target-org-{$orgId}",
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $orgId;
}

it('returns audit log entries for admin', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/audit-log', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'meta',
        ]);
});

it('returns audit log entries for super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->getJson('/api/v1/admin/audit-log', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
});

it('returns audit entries after performing an admin action', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization();

    // Perform a suspend action to generate an audit entry
    $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'Suspending organization to test audit log entries.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(204);

    // Now check the audit log
    $response = $this->getJson('/api/v1/admin/audit-log', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('supports filtering audit log by action', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization();

    // Create an audit entry via suspend action
    $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'Suspending organization to verify audit action filter.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(204);

    $response = $this->getJson('/api/v1/admin/audit-log?action=organization.suspended', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('supports cursor-based pagination for audit log', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/audit-log?per_page=2', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'meta' => ['per_page', 'has_more', 'next_cursor'],
        ]);
});

it('returns 403 for support role trying to view audit log', function () {
    $auth = createAdminAndGetToken('support');

    $response = $this->getJson('/api/v1/admin/audit-log', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/admin/audit-log')->assertStatus(401);
});

it('returns 403 for regular user trying to view audit log', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/audit-log', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});
