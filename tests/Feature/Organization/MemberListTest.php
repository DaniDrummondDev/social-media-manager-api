<?php

declare(strict_types=1);

use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb(['email' => 'list@example.com']);
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];
});

it('lists organization members', function () {
    $headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    $response = $this->withHeaders($headers)->getJson("/api/v1/organizations/{$this->orgId}/members");

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('requires authentication', function () {
    $this->getJson("/api/v1/organizations/{$this->orgId}/members")
        ->assertStatus(401);
});

it('requires org context', function () {
    $headers = $this->authHeaders($this->user['id'], '', $this->user['email']);

    $this->withHeaders($headers)->getJson("/api/v1/organizations/{$this->orgId}/members")
        ->assertStatus(403);
});
