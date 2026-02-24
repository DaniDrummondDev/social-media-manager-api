<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeTwoFactorService;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->app->singleton(TwoFactorServiceInterface::class, FakeTwoFactorService::class);
    $this->fakeTotp = app(TwoFactorServiceInterface::class);
    $this->user = $this->createUserInDb(['email' => '2fa@example.com', 'plain_password' => 'SecureP@ss1']);
});

function enable2faForUser(string $userId, FakeTwoFactorService $service): void
{
    $encryptedSecret = $service->encryptSecret(FakeTwoFactorService::TEST_SECRET);
    $recoveryCodes = json_encode($service->generateRecoveryCodes(), JSON_THROW_ON_ERROR);

    DB::table('users')->where('id', $userId)->update([
        'two_factor_enabled' => true,
        'two_factor_secret' => $encryptedSecret,
        'recovery_codes' => $recoveryCodes,
    ]);
}

it('enables 2fa setup', function () {
    $headers = $this->authHeaders($this->user['id'], '', $this->user['email']);

    $response = $this->withHeaders($headers)->postJson('/api/v1/auth/2fa/enable');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['secret', 'qr_code_url', 'qr_code_svg']]);
});

it('confirms 2fa with valid code', function () {
    $headers = $this->authHeaders($this->user['id'], '', $this->user['email']);

    // Enable first to get secret
    $enableResponse = $this->withHeaders($headers)->postJson('/api/v1/auth/2fa/enable');
    $secret = $enableResponse->json('data.secret');

    $response = $this->withHeaders($headers)->postJson('/api/v1/auth/2fa/confirm', [
        'secret' => $secret,
        'otp_code' => '123456',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.message', fn ($msg) => str_contains($msg, '2FA') || str_contains($msg, '2fa') || str_contains($msg, 'Two-factor'));
});

it('disables 2fa with correct password', function () {
    enable2faForUser($this->user['id'], $this->fakeTotp);

    $headers = $this->authHeaders($this->user['id'], '', $this->user['email']);

    $response = $this->withHeaders($headers)->postJson('/api/v1/auth/2fa/disable', [
        'password' => 'SecureP@ss1',
    ]);

    $response->assertOk();
});

it('verifies 2fa login with temp token', function () {
    enable2faForUser($this->user['id'], $this->fakeTotp);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => '2fa@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $tempToken = $loginResponse->json('data.temp_token');

    $response = $this->postJson('/api/v1/auth/2fa/verify', [
        'temp_token' => $tempToken,
        'otp_code' => '123456',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'token_type', 'expires_in']]);
});

it('rejects 2fa verify with wrong code', function () {
    enable2faForUser($this->user['id'], $this->fakeTotp);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => '2fa@example.com',
        'password' => 'SecureP@ss1',
    ]);

    $tempToken = $loginResponse->json('data.temp_token');

    $response = $this->postJson('/api/v1/auth/2fa/verify', [
        'temp_token' => $tempToken,
        'otp_code' => '000000',
    ]);

    $response->assertStatus(422);
});

it('requires authentication to enable 2fa', function () {
    $response = $this->postJson('/api/v1/auth/2fa/enable');

    $response->assertStatus(401);
});

it('requires authentication to disable 2fa', function () {
    $response = $this->postJson('/api/v1/auth/2fa/disable', [
        'password' => 'SecureP@ss1',
    ]);

    $response->assertStatus(401);
});
