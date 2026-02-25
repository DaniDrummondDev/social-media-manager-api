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

function createTargetUser(string $status = 'active'): string
{
    $userId = (string) \Illuminate\Support\Str::uuid();

    DB::table('users')->insert([
        'id' => $userId,
        'name' => 'Target User',
        'email' => "target-{$userId}@test.com",
        'password' => 'hashed',
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $userId;
}

it('force verifies a user as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/force-verify", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('force verifies a user as admin', function () {
    $auth = createAdminAndGetToken('admin');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/force-verify", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('force verifies a user as support', function () {
    $auth = createAdminAndGetToken('support');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/force-verify", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('resets password for a user as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/reset-password", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('resets password for a user as admin', function () {
    $auth = createAdminAndGetToken('admin');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/reset-password", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('resets password for a user as support', function () {
    $auth = createAdminAndGetToken('support');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/reset-password", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 401 without authentication for force-verify', function () {
    $targetUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$targetUserId}/force-verify")
        ->assertStatus(401);
});

it('returns 401 without authentication for reset-password', function () {
    $targetUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$targetUserId}/reset-password")
        ->assertStatus(401);
});
