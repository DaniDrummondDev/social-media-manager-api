<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use Tests\Support\FakeTwoFactorService;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
});

it('logs in with valid credentials', function () {
    $this->createUserInDb([
        'email' => 'login@example.com',
        'plain_password' => 'SecureP@ss1',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'token_type', 'expires_in']])
        ->assertJsonPath('data.token_type', 'Bearer');
});

it('returns 2fa challenge when 2fa is enabled', function () {
    $this->app->singleton(TwoFactorServiceInterface::class, FakeTwoFactorService::class);
    $fakeTotp = app(TwoFactorServiceInterface::class);

    $user = $this->createUserInDb([
        'email' => '2fa@example.com',
        'plain_password' => 'SecureP@ss1',
        'two_factor_enabled' => true,
        'two_factor_secret' => $fakeTotp->encryptSecret(FakeTwoFactorService::TEST_SECRET),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => '2fa@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.requires_2fa', true)
        ->assertJsonStructure(['data' => ['requires_2fa', 'temp_token']]);
});

it('rejects wrong password', function () {
    $this->createUserInDb([
        'email' => 'wrong@example.com',
        'plain_password' => 'SecureP@ss1',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'wrong@example.com',
        'password' => 'WrongPassword1!',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('errors.0.code', 'AUTHENTICATION_ERROR');
});

it('rejects unknown email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('errors.0.code', 'AUTHENTICATION_ERROR');
});

it('rejects inactive user', function () {
    $this->createUserInDb([
        'email' => 'inactive@example.com',
        'plain_password' => 'SecureP@ss1',
        'status' => 'suspended',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'inactive@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $response->assertStatus(401);
});

it('rejects missing fields', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertStatus(422);
});
