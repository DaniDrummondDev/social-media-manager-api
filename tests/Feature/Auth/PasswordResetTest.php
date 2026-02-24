<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    Mail::fake();
    $this->setUpAuth();
    $this->user = $this->createUserInDb(['email' => 'reset@example.com', 'plain_password' => 'OldP@ss123']);
});

it('sends forgot password email', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'reset@example.com',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['message']]);

    Mail::assertQueued(\App\Infrastructure\Identity\Notifications\ResetPasswordMail::class);
});

it('silently succeeds for unknown email', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertOk();
    Mail::assertNothingQueued();
});

it('resets password with valid token', function () {
    $token = Str::random(64);

    DB::table('password_resets')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user['id'],
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addHour(),
    ]);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'password' => 'NewSecureP@ss1',
        'password_confirmation' => 'NewSecureP@ss1',
    ]);

    $response->assertOk();

    // Verify can login with new password
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'reset@example.com',
        'password' => 'NewSecureP@ss1',
    ]);

    $loginResponse->assertOk();
});

it('rejects expired reset token', function () {
    $token = Str::random(64);

    DB::table('password_resets')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user['id'],
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->subHour(),
    ]);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'password' => 'NewSecureP@ss1',
        'password_confirmation' => 'NewSecureP@ss1',
    ]);

    $response->assertStatus(422);
});
