<?php

declare(strict_types=1);

use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
});

it('refreshes tokens with valid refresh token', function () {
    $user = $this->createUserInDb(['email' => 'refresh@example.com', 'plain_password' => 'SecureP@ss1']);

    // Login to get tokens
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'refresh@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $refreshToken = $loginResponse->json('data.refresh_token');

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'token_type', 'expires_in']]);

    // New refresh token should be different (rotation)
    expect($response->json('data.refresh_token'))->not->toBe($refreshToken);
});

it('rejects invalid refresh token', function () {
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'invalid-token',
    ]);

    $response->assertStatus(401);
});

it('detects token reuse', function () {
    $user = $this->createUserInDb(['email' => 'reuse@example.com', 'plain_password' => 'SecureP@ss1']);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'reuse@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $refreshToken = $loginResponse->json('data.refresh_token');

    // First refresh succeeds
    $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshToken])
        ->assertOk();

    // Reusing the same token should fail (already revoked)
    $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshToken])
        ->assertStatus(401);
});

it('logs out successfully', function () {
    $user = $this->createUserInDb(['email' => 'logout@example.com', 'plain_password' => 'SecureP@ss1']);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'logout@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $headers = $this->authHeaders($user['id'], '', $user['email']);

    $response = $this->withHeaders($headers)->postJson('/api/v1/auth/logout');

    $response->assertNoContent();
});
