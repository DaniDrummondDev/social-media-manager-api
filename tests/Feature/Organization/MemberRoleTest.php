<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->owner = $this->createUserInDb(['email' => 'owner@example.com']);
    $this->orgData = $this->createOrgWithOwner($this->owner['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->ownerHeaders = $this->authHeaders($this->owner['id'], $this->orgId, $this->owner['email']);
});

it('changes a member role', function () {
    $member = $this->createUserInDb(['email' => 'promote@example.com']);
    DB::table('organization_members')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'user_id' => $member['id'],
        'role' => 'member',
        'invited_by' => $this->owner['id'],
        'joined_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->ownerHeaders)
        ->putJson("/api/v1/organizations/{$this->orgId}/members/{$member['id']}/role", [
            'role' => 'admin',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.role', 'admin');
});

it('rejects role change by member without permission', function () {
    $member = $this->createUserInDb(['email' => 'regular@example.com']);
    $target = $this->createUserInDb(['email' => 'target@example.com']);

    foreach ([['user' => $member, 'role' => 'member'], ['user' => $target, 'role' => 'member']] as $data) {
        DB::table('organization_members')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->orgId,
            'user_id' => $data['user']['id'],
            'role' => $data['role'],
            'invited_by' => $this->owner['id'],
            'joined_at' => now()->toDateTimeString(),
        ]);
    }

    $memberHeaders = $this->authHeaders($member['id'], $this->orgId, $member['email'], 'member');

    $response = $this->withHeaders($memberHeaders)
        ->putJson("/api/v1/organizations/{$this->orgId}/members/{$target['id']}/role", [
            'role' => 'admin',
        ]);

    $response->assertStatus(403);
});

it('rejects invalid role value', function () {
    $member = $this->createUserInDb(['email' => 'invalid@example.com']);
    DB::table('organization_members')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'user_id' => $member['id'],
        'role' => 'member',
        'invited_by' => $this->owner['id'],
        'joined_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->ownerHeaders)
        ->putJson("/api/v1/organizations/{$this->orgId}/members/{$member['id']}/role", [
            'role' => 'superadmin',
        ]);

    $response->assertStatus(422);
});

it('prevents demoting the last owner', function () {
    $response = $this->withHeaders($this->ownerHeaders)
        ->putJson("/api/v1/organizations/{$this->orgId}/members/{$this->owner['id']}/role", [
            'role' => 'admin',
        ]);

    $response->assertStatus(422);
});

it('requires org context', function () {
    $headers = $this->authHeaders($this->owner['id'], '', $this->owner['email']);
    $member = $this->createUserInDb(['email' => 'ctx@example.com']);

    $this->withHeaders($headers)
        ->putJson("/api/v1/organizations/{$this->orgId}/members/{$member['id']}/role", [
            'role' => 'admin',
        ])
        ->assertStatus(403);
});
