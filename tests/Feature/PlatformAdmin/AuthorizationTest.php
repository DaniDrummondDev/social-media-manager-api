<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

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

it('returns 403 for regular user on dashboard endpoint', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/dashboard', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on organizations list endpoint', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/organizations', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on organization detail endpoint', function () {
    $auth = createRegularUserToken();
    $fakeOrgId = (string) \Illuminate\Support\Str::uuid();

    $this->getJson("/api/v1/admin/organizations/{$fakeOrgId}", [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on suspend organization endpoint', function () {
    $auth = createRegularUserToken();
    $fakeOrgId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/organizations/{$fakeOrgId}/suspend", [
        'reason' => 'Regular user should not be able to suspend.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on unsuspend organization endpoint', function () {
    $auth = createRegularUserToken();
    $fakeOrgId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/organizations/{$fakeOrgId}/unsuspend", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on delete organization endpoint', function () {
    $auth = createRegularUserToken();
    $fakeOrgId = (string) \Illuminate\Support\Str::uuid();

    $this->deleteJson("/api/v1/admin/organizations/{$fakeOrgId}", [
        'reason' => 'Regular user should not be able to delete.',
        'confirm' => true,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on users list endpoint', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/users', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on user detail endpoint', function () {
    $auth = createRegularUserToken();
    $fakeUserId = (string) \Illuminate\Support\Str::uuid();

    $this->getJson("/api/v1/admin/users/{$fakeUserId}", [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on ban user endpoint', function () {
    $auth = createRegularUserToken();
    $fakeUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$fakeUserId}/ban", [
        'reason' => 'Regular user should not be able to ban.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on unban user endpoint', function () {
    $auth = createRegularUserToken();
    $fakeUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$fakeUserId}/unban", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on force-verify endpoint', function () {
    $auth = createRegularUserToken();
    $fakeUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$fakeUserId}/force-verify", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on reset-password endpoint', function () {
    $auth = createRegularUserToken();
    $fakeUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$fakeUserId}/reset-password", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on plans list endpoint', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/plans', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on create plan endpoint', function () {
    $auth = createRegularUserToken();

    $this->postJson('/api/v1/admin/plans', [
        'name' => 'Test',
        'slug' => 'test',
        'price_monthly_cents' => 0,
        'price_yearly_cents' => 0,
        'currency' => 'BRL',
        'limits' => [],
        'features' => [],
        'sort_order' => 1,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on config endpoint', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/config', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on update config endpoint', function () {
    $auth = createRegularUserToken();

    $this->patchJson('/api/v1/admin/config', [
        'configs' => [['key' => 'maintenance_mode', 'value' => true]],
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});

it('returns 403 for regular user on audit log endpoint', function () {
    $auth = createRegularUserToken();

    $this->getJson('/api/v1/admin/audit-log', [
        'Authorization' => "Bearer {$auth['token']}",
    ])->assertStatus(403);
});
