<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    Mail::fake();
    $this->setUpAuth();
});

it('registers a new user', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'SecureP@ss1',
        'password_confirmation' => 'SecureP@ss1',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'João Silva')
        ->assertJsonPath('data.email', 'joao@example.com')
        ->assertJsonPath('data.email_verified', false)
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'timezone', 'created_at']]);

    Mail::assertQueued(\App\Infrastructure\Identity\Notifications\VerifyEmailMail::class);
});

it('rejects duplicate email', function () {
    $this->createUserInDb(['email' => 'duplicate@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Another User',
        'email' => 'duplicate@example.com',
        'password' => 'SecureP@ss1',
        'password_confirmation' => 'SecureP@ss1',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'EMAIL_ALREADY_IN_USE');
});

it('rejects weak password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'User',
        'email' => 'weak@example.com',
        'password' => '123',
        'password_confirmation' => '123',
    ]);

    $response->assertStatus(422);
});

it('rejects missing required fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('rejects mismatched password confirmation', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'User',
        'email' => 'mismatch@example.com',
        'password' => 'SecureP@ss1',
        'password_confirmation' => 'DifferentPass1!',
    ]);

    $response->assertStatus(422);
});
