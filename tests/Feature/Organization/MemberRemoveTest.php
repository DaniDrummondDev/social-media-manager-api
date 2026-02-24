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

it('removes a member', function () {
    $member = $this->createUserInDb(['email' => 'removeme@example.com']);
    DB::table('organization_members')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'user_id' => $member['id'],
        'role' => 'member',
        'invited_by' => $this->owner['id'],
        'joined_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->ownerHeaders)
        ->deleteJson("/api/v1/organizations/{$this->orgId}/members/{$member['id']}");

    $response->assertNoContent();
});

it('rejects removal by member without permission', function () {
    $member = $this->createUserInDb(['email' => 'regular@example.com']);
    $target = $this->createUserInDb(['email' => 'target@example.com']);

    foreach ([$member, $target] as $user) {
        DB::table('organization_members')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->orgId,
            'user_id' => $user['id'],
            'role' => 'member',
            'invited_by' => $this->owner['id'],
            'joined_at' => now()->toDateTimeString(),
        ]);
    }

    $memberHeaders = $this->authHeaders($member['id'], $this->orgId, $member['email'], 'member');

    $response = $this->withHeaders($memberHeaders)
        ->deleteJson("/api/v1/organizations/{$this->orgId}/members/{$target['id']}");

    $response->assertStatus(403);
});

it('prevents removing the last owner', function () {
    $response = $this->withHeaders($this->ownerHeaders)
        ->deleteJson("/api/v1/organizations/{$this->orgId}/members/{$this->owner['id']}");

    $response->assertStatus(422);
});

it('requires org context', function () {
    $headers = $this->authHeaders($this->owner['id'], '', $this->owner['email']);
    $member = $this->createUserInDb(['email' => 'ctx@example.com']);

    $this->withHeaders($headers)
        ->deleteJson("/api/v1/organizations/{$this->orgId}/members/{$member['id']}")
        ->assertStatus(403);
});
