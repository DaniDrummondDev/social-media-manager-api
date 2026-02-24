<?php

declare(strict_types=1);

use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb(['email' => 'switch@example.com']);
    $this->headers = $this->authHeaders($this->user['id'], '', $this->user['email']);
});

it('switches organization and returns new tokens', function () {
    $orgData = $this->createOrgWithOwner($this->user['id']);
    $orgId = $orgData['org']['id'];

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/organizations/switch', [
        'organization_id' => $orgId,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'token_type', 'expires_in']]);
});

it('rejects switch to non-member organization', function () {
    $otherUser = $this->createUserInDb(['email' => 'other@example.com']);
    $orgData = $this->createOrgWithOwner($otherUser['id']);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/organizations/switch', [
        'organization_id' => $orgData['org']['id'],
    ]);

    $response->assertStatus(403);
});

it('rejects switch to inactive organization', function () {
    $orgData = $this->createOrgWithOwner($this->user['id'], ['status' => 'suspended']);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/organizations/switch', [
        'organization_id' => $orgData['org']['id'],
    ]);

    $response->assertStatus(403);
});

it('requires authentication', function () {
    $this->postJson('/api/v1/organizations/switch', [
        'organization_id' => 'some-id',
    ])->assertStatus(401);
});
