<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PlanSeeder::class);
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

it('lists all plans for admin', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->getJson('/api/v1/admin/plans', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(4);
});

it('lists all plans for super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->getJson('/api/v1/admin/plans', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(4);
});

it('returns 403 for support role trying to list plans', function () {
    $auth = createAdminAndGetToken('support');

    $response = $this->getJson('/api/v1/admin/plans', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('creates a plan as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->postJson('/api/v1/admin/plans', [
        'name' => 'Starter',
        'slug' => 'starter',
        'description' => 'Test plan for starter tier',
        'price_monthly_cents' => 4900,
        'price_yearly_cents' => 49900,
        'currency' => 'BRL',
        'limits' => ['members' => 2, 'social_accounts' => 5],
        'features' => ['ai_generation_basic' => true],
        'sort_order' => 5,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(201);
    expect($response->json('data.id'))->not->toBeNull();
});

it('returns 403 for admin role trying to create plan', function () {
    $auth = createAdminAndGetToken('admin');

    $response = $this->postJson('/api/v1/admin/plans', [
        'name' => 'Starter',
        'slug' => 'starter',
        'description' => 'Test plan',
        'price_monthly_cents' => 0,
        'price_yearly_cents' => 0,
        'currency' => 'BRL',
        'limits' => [],
        'features' => [],
        'sort_order' => 5,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 403 for support role trying to create plan', function () {
    $auth = createAdminAndGetToken('support');

    $response = $this->postJson('/api/v1/admin/plans', [
        'name' => 'Starter',
        'slug' => 'starter',
        'description' => 'Test plan',
        'price_monthly_cents' => 0,
        'price_yearly_cents' => 0,
        'currency' => 'BRL',
        'limits' => [],
        'features' => [],
        'sort_order' => 5,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('updates a plan as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $planId = PlanSeeder::CREATOR_PLAN_ID;

    $response = $this->patchJson("/api/v1/admin/plans/{$planId}", [
        'name' => 'Creator Pro',
        'description' => 'Updated creator plan',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 403 for admin role trying to update plan', function () {
    $auth = createAdminAndGetToken('admin');

    $planId = PlanSeeder::CREATOR_PLAN_ID;

    $response = $this->patchJson("/api/v1/admin/plans/{$planId}", [
        'name' => 'Creator Updated',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('deactivates a plan as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');

    $planId = PlanSeeder::AGENCY_PLAN_ID;

    $response = $this->postJson("/api/v1/admin/plans/{$planId}/deactivate", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 403 for admin role trying to deactivate plan', function () {
    $auth = createAdminAndGetToken('admin');

    $planId = PlanSeeder::AGENCY_PLAN_ID;

    $response = $this->postJson("/api/v1/admin/plans/{$planId}/deactivate", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 422 when creating plan with missing required fields', function () {
    $auth = createAdminAndGetToken('super_admin');

    $response = $this->postJson('/api/v1/admin/plans', [
        'name' => 'Incomplete Plan',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/admin/plans')->assertStatus(401);
});

it('returns 403 for regular user trying to list plans', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/plans', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});
