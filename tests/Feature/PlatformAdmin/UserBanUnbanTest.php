<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('bans a user as admin', function () {
    $auth = createAdminAndGetToken('admin');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/ban", [
        'reason' => 'User violated community guidelines repeatedly.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);

    // Verify the user was banned in the database
    $user = DB::table('users')->where('id', $targetUserId)->first();
    expect($user->status)->toBe('suspended');
});

it('bans a user as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/ban", [
        'reason' => 'Spam account detected and verified by admin.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('unbans a banned user as admin', function () {
    $auth = createAdminAndGetToken('admin');
    $targetUserId = createTargetUser('suspended');

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/unban", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);

    // Verify the user was unbanned in the database
    $user = DB::table('users')->where('id', $targetUserId)->first();
    expect($user->status)->toBe('active');
});

it('unbans a banned user as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetUserId = createTargetUser('suspended');

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/unban", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 422 when banning already banned user', function () {
    $auth = createAdminAndGetToken('admin');
    $targetUserId = createTargetUser('suspended');

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/ban", [
        'reason' => 'Attempting to ban an already banned user.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 422 when unbanning a non-banned user', function () {
    $auth = createAdminAndGetToken('admin');
    $targetUserId = createTargetUser('active');

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/unban", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 403 for support role trying to ban user', function () {
    $auth = createAdminAndGetToken('support');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/ban", [
        'reason' => 'Support should not be able to ban users directly.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 403 for support role trying to unban user', function () {
    $auth = createAdminAndGetToken('support');
    $targetUserId = createTargetUser('suspended');

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/unban", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 422 when ban reason is missing', function () {
    $auth = createAdminAndGetToken('admin');
    $targetUserId = createTargetUser();

    $response = $this->postJson("/api/v1/admin/users/{$targetUserId}/ban", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 401 without authentication for ban', function () {
    $targetUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$targetUserId}/ban", [
        'reason' => 'Attempting ban without authentication token.',
    ])->assertStatus(401);
});

it('returns 401 without authentication for unban', function () {
    $targetUserId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/users/{$targetUserId}/unban")
        ->assertStatus(401);
});
