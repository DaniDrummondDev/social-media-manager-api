<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

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
