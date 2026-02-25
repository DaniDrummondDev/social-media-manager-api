<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

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
