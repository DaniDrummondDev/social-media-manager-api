<?php

declare(strict_types=1);

use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb(['email' => 'org@example.com']);
    $this->headers = $this->authHeaders($this->user['id'], '', $this->user['email']);
});

it('creates an organization', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/organizations', [
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Acme Corp')
        ->assertJsonPath('data.slug', 'acme-corp')
        ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'timezone', 'status']]);
});

it('lists user organizations', function () {
    $this->createOrgWithOwner($this->user['id'], ['name' => 'Org A', 'slug' => 'org-a']);
    $this->createOrgWithOwner($this->user['id'], ['name' => 'Org B', 'slug' => 'org-b']);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/organizations');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('updates an organization', function () {
    $orgData = $this->createOrgWithOwner($this->user['id']);
    $orgId = $orgData['org']['id'];
    $headers = $this->authHeaders($this->user['id'], $orgId, $this->user['email']);

    $response = $this->withHeaders($headers)->putJson("/api/v1/organizations/{$orgId}", [
        'name' => 'Updated Org',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Org');
});

it('rejects duplicate slug on create', function () {
    $this->createOrgWithOwner($this->user['id'], ['slug' => 'taken-slug']);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/organizations', [
        'name' => 'Another Org',
        'slug' => 'taken-slug',
    ]);

    // DB unique constraint prevents duplicate — returns 500 (no application-level check yet)
    $response->assertStatus(500);
});

it('requires authentication', function () {
    $this->postJson('/api/v1/organizations', [
        'name' => 'No Auth',
        'slug' => 'no-auth',
    ])->assertStatus(401);

    $this->getJson('/api/v1/organizations')->assertStatus(401);
});

it('requires org context for update', function () {
    $orgData = $this->createOrgWithOwner($this->user['id']);
    $orgId = $orgData['org']['id'];

    // Token without org context
    $response = $this->withHeaders($this->headers)->putJson("/api/v1/organizations/{$orgId}", [
        'name' => 'Update Without Context',
    ]);

    $response->assertStatus(403);
});
