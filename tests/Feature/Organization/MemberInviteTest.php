<?php

declare(strict_types=1);

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\Mail;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    Mail::fake();
    $this->setUpAuth();
    $this->owner = $this->createUserInDb(['email' => 'owner@example.com']);
    $this->orgData = $this->createOrgWithOwner($this->owner['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->ownerHeaders = $this->authHeaders($this->owner['id'], $this->orgId, $this->owner['email']);
});

it('invites a member', function () {
    $response = $this->withHeaders($this->ownerHeaders)->postJson(
        "/api/v1/organizations/{$this->orgId}/members/invite",
        ['email' => 'newmember@example.com', 'role' => 'member'],
    );

    $response->assertOk()
        ->assertJsonStructure(['data' => ['message']]);
});

it('accepts an invite', function () {
    // Create invite directly
    $invite = OrganizationInvite::create(
        organizationId: Uuid::fromString($this->orgId),
        email: Email::fromString('accept@example.com'),
        role: OrganizationRole::Member,
        invitedBy: Uuid::fromString($this->owner['id']),
    );

    app(\App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface::class)->create($invite);

    // Create user who will accept
    $acceptUser = $this->createUserInDb(['email' => 'accept@example.com']);
    $acceptHeaders = $this->authHeaders($acceptUser['id'], '', $acceptUser['email']);

    $response = $this->withHeaders($acceptHeaders)->postJson('/api/v1/invites/accept', [
        'token' => $invite->token,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'organization_id', 'user_id', 'role']]);
});

it('rejects duplicate invite', function () {
    // First invite
    $this->withHeaders($this->ownerHeaders)->postJson(
        "/api/v1/organizations/{$this->orgId}/members/invite",
        ['email' => 'dup@example.com', 'role' => 'member'],
    )->assertOk();

    // Second invite for same email
    $response = $this->withHeaders($this->ownerHeaders)->postJson(
        "/api/v1/organizations/{$this->orgId}/members/invite",
        ['email' => 'dup@example.com', 'role' => 'member'],
    );

    $response->assertStatus(422);
});

it('rejects invite from member without permission', function () {
    // Create a regular member
    $member = $this->createUserInDb(['email' => 'member@example.com']);
    \Illuminate\Support\Facades\DB::table('organization_members')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'organization_id' => $this->orgId,
        'user_id' => $member['id'],
        'role' => 'member',
        'invited_by' => $this->owner['id'],
        'joined_at' => now()->toDateTimeString(),
    ]);

    $memberHeaders = $this->authHeaders($member['id'], $this->orgId, $member['email'], 'member');

    $response = $this->withHeaders($memberHeaders)->postJson(
        "/api/v1/organizations/{$this->orgId}/members/invite",
        ['email' => 'another@example.com', 'role' => 'member'],
    );

    $response->assertStatus(403);
});

it('requires org context to invite', function () {
    $headers = $this->authHeaders($this->owner['id'], '', $this->owner['email']);

    $this->withHeaders($headers)->postJson(
        "/api/v1/organizations/{$this->orgId}/members/invite",
        ['email' => 'test@example.com', 'role' => 'member'],
    )->assertStatus(403);
});
