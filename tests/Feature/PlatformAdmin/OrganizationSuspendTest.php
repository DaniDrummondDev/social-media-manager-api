<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('suspends an organization with valid reason as admin', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'Violation of terms of service detected during review.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);

    // Verify the organization was suspended in the database
    $org = DB::table('organizations')->where('id', $targetOrgId)->first();
    expect($org->status)->toBe('suspended');
});

it('suspends an organization as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'Organization suspended for policy violation and review.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 422 when organization is already suspended', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization('suspended');

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'Attempting to suspend already suspended org.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 403 for support role trying to suspend', function () {
    $auth = createAdminAndGetToken('support');
    $targetOrgId = createTargetOrganization();

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'Support should not be able to suspend organizations.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 422 when reason is missing', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 422 when reason is too short', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'short',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 401 without authentication', function () {
    $targetOrgId = (string) \Illuminate\Support\Str::uuid();

    $this->postJson("/api/v1/admin/organizations/{$targetOrgId}/suspend", [
        'reason' => 'Attempting to suspend without authentication token.',
    ])->assertStatus(401);
});
