<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use Illuminate\Support\Facades\DB;

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
        'joined_at' => now(),
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
        'user_id' => $userId, 'role' => 'owner', 'joined_at' => now(),
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
