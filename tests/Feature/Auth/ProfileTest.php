<?php

declare(strict_types=1);

use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb([
        'email' => 'profile@example.com',
        'plain_password' => 'SecureP@ss1',
    ]);
    $this->headers = $this->authHeaders($this->user['id'], '', $this->user['email']);
});

it('shows user profile', function () {
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/profile');

    $response->assertOk()
        ->assertJsonPath('data.email', 'profile@example.com')
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'timezone', 'email_verified', 'two_factor_enabled']]);
});

it('updates profile name', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/profile', [
        'name' => 'Updated Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

it('updates profile timezone', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/profile', [
        'timezone' => 'Europe/London',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.timezone', 'Europe/London');
});

it('changes email with correct password', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/profile/email', [
        'email' => 'newemail@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $response->assertOk();
});

it('rejects email change with wrong password', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/profile/email', [
        'email' => 'newemail@example.com',
        'password' => 'WrongPass1!',
    ]);

    $response->assertStatus(401);
});

it('changes password with correct current password', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/profile/password', [
        'current_password' => 'SecureP@ss1',
        'password' => 'NewSecureP@ss1',
        'password_confirmation' => 'NewSecureP@ss1',
    ]);

    $response->assertOk();
});

it('rejects password change with wrong current password', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/profile/password', [
        'current_password' => 'WrongPass1!',
        'password' => 'NewSecureP@ss1',
        'password_confirmation' => 'NewSecureP@ss1',
    ]);

    $response->assertStatus(401);
});

it('requires authentication for profile', function () {
    $this->getJson('/api/v1/profile')->assertStatus(401);
    $this->putJson('/api/v1/profile', ['name' => 'Test'])->assertStatus(401);
});

it('rejects invalid timezone', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/profile', [
        'timezone' => 'Invalid/Timezone',
    ]);

    $response->assertStatus(422);
});
