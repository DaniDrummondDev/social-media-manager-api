<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    // User A owns Org A
    $this->userA = $this->createUserInDb(['email' => 'userA@example.com']);
    $this->orgA = $this->createOrgWithOwner($this->userA['id'], ['name' => 'Org A', 'slug' => 'org-a']);

    // User B owns Org B
    $this->userB = $this->createUserInDb(['email' => 'userB@example.com']);
    $this->orgB = $this->createOrgWithOwner($this->userB['id'], ['name' => 'Org B', 'slug' => 'org-b']);

    // Add a member to org B for removal/role tests
    $this->memberB = $this->createUserInDb(['email' => 'memberB@example.com']);
    DB::table('organization_members')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgB['org']['id'],
        'user_id' => $this->memberB['id'],
        'role' => 'member',
        'invited_by' => $this->userB['id'],
        'joined_at' => now()->toDateTimeString(),
    ]);
});

it('user A cannot invite to org B', function () {
    // User A with org A context tries to invite on org B endpoint
    $headersA = $this->authHeaders($this->userA['id'], $this->orgA['org']['id'], $this->userA['email']);
    $orgBId = $this->orgB['org']['id'];

    $response = $this->withHeaders($headersA)->postJson(
        "/api/v1/organizations/{$orgBId}/members/invite",
        ['email' => 'cross@example.com', 'role' => 'member'],
    );

    $response->assertStatus(403);
});

it('user A cannot remove member from org B', function () {
    $headersA = $this->authHeaders($this->userA['id'], $this->orgA['org']['id'], $this->userA['email']);
    $orgBId = $this->orgB['org']['id'];

    $response = $this->withHeaders($headersA)->deleteJson(
        "/api/v1/organizations/{$orgBId}/members/{$this->memberB['id']}",
    );

    $response->assertStatus(403);
});

it('user A cannot change role in org B', function () {
    $headersA = $this->authHeaders($this->userA['id'], $this->orgA['org']['id'], $this->userA['email']);
    $orgBId = $this->orgB['org']['id'];

    $response = $this->withHeaders($headersA)->putJson(
        "/api/v1/organizations/{$orgBId}/members/{$this->memberB['id']}/role",
        ['role' => 'admin'],
    );

    $response->assertStatus(403);
});

it('user A cannot switch to org B', function () {
    $headersA = $this->authHeaders($this->userA['id'], '', $this->userA['email']);
    $orgBId = $this->orgB['org']['id'];

    $response = $this->withHeaders($headersA)->postJson('/api/v1/organizations/switch', [
        'organization_id' => $orgBId,
    ]);

    $response->assertStatus(403);
});

it('user A cannot update org B', function () {
    $headersA = $this->authHeaders($this->userA['id'], $this->orgA['org']['id'], $this->userA['email']);
    $orgBId = $this->orgB['org']['id'];

    $response = $this->withHeaders($headersA)->putJson("/api/v1/organizations/{$orgBId}", [
        'name' => 'Hijacked Org',
    ]);

    $response->assertStatus(403);
});

it('user A cannot list members of org B', function () {
    $headersA = $this->authHeaders($this->userA['id'], $this->orgA['org']['id'], $this->userA['email']);
    $orgBId = $this->orgB['org']['id'];

    $response = $this->withHeaders($headersA)->getJson("/api/v1/organizations/{$orgBId}/members");

    // User A should not be able to see Org B members - proper authorization is enforced
    $response->assertStatus(403);
});
