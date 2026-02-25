<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes an organization as super_admin', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->deleteJson("/api/v1/admin/organizations/{$targetOrgId}", [
        'reason' => 'Organization deleted due to permanent policy violation.',
        'confirm' => true,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(204);
});

it('returns 403 for admin role trying to delete organization', function () {
    $auth = createAdminAndGetToken('admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->deleteJson("/api/v1/admin/organizations/{$targetOrgId}", [
        'reason' => 'Admin should not be able to delete organizations.',
        'confirm' => true,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 403 for support role trying to delete organization', function () {
    $auth = createAdminAndGetToken('support');
    $targetOrgId = createTargetOrganization();

    $response = $this->deleteJson("/api/v1/admin/organizations/{$targetOrgId}", [
        'reason' => 'Support should not be able to delete organizations.',
        'confirm' => true,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(403);
});

it('returns 422 when reason is missing', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->deleteJson("/api/v1/admin/organizations/{$targetOrgId}", [
        'confirm' => true,
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 422 when confirm flag is missing', function () {
    $auth = createAdminAndGetToken('super_admin');
    $targetOrgId = createTargetOrganization();

    $response = $this->deleteJson("/api/v1/admin/organizations/{$targetOrgId}", [
        'reason' => 'Trying to delete without confirmation flag set.',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(422);
});

it('returns 401 without authentication', function () {
    $targetOrgId = (string) \Illuminate\Support\Str::uuid();

    $this->deleteJson("/api/v1/admin/organizations/{$targetOrgId}", [
        'reason' => 'Trying to delete without authentication token.',
        'confirm' => true,
    ])->assertStatus(401);
});
