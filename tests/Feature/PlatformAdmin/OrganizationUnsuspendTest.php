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

it('unsuspends a suspended organization as admin', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization('suspended');

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/unsuspend", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);

    // Verify the organization was reactivated in the database
    $org = DB::table('organizations')->where('id', $targetOrgId)->first();
    expect($org->status)->toBe('active');
});

it('unsuspends a suspended organization as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetOrgId = createTargetOrganization('suspended');

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/unsuspend", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 422 when organization is not suspended', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization('active');

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/unsuspend", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 403 for support role trying to unsuspend', function () {
    $auth = createAdminAndGetToken('support');
    $targetOrgId = createTargetOrganization('suspended');

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/unsuspend", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 401 without authentication', function () {
    $targetOrgId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/unsuspend")
        ->assertStatus(401);
});
