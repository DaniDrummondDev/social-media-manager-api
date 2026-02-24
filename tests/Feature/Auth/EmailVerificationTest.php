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
    $this->user = $this->createUserInDb(['email' => 'verify@example.com', 'email_verified_at' => null]);
});

it('verifies email with valid token', function () {
    // Generate a verification token
    $token = Str::random(64);
    $tokenHash = hash('sha256', $token);

    DB::table('email_verifications')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user['id'],
        'token_hash' => $tokenHash,
        'expires_at' => now()->addHours(24),
    ]);

    $response = $this->postJson('/api/v1/auth/verify-email', [
        'token' => $token,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['message']]);
});

it('rejects expired verification token', function () {
    $token = Str::random(64);

    DB::table('email_verifications')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $this->user['id'],
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->subHour(),
    ]);

    $response = $this->postJson('/api/v1/auth/verify-email', [
        'token' => $token,
    ]);

    $response->assertStatus(422);
});

it('rejects invalid verification token', function () {
    $response = $this->postJson('/api/v1/auth/verify-email', [
        'token' => 'invalid-token',
    ]);

    $response->assertStatus(422);
});

it('rejects missing token', function () {
    $response = $this->postJson('/api/v1/auth/verify-email', []);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});
