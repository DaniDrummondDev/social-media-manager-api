<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\PasswordResetServiceInterface;
use App\Application\Identity\Exceptions\InvalidTokenException;
use App\Infrastructure\Identity\Notifications\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    Mail::fake();
    $this->service = app(PasswordResetServiceInterface::class);
    $this->user = $this->createUserInDb();
});

it('sends reset email for existing user', function () {
    $this->service->sendResetEmail($this->user['email']);

    Mail::assertQueued(ResetPasswordMail::class);

    $record = DB::table('password_resets')
        ->where('user_id', $this->user['id'])
        ->first();

    expect($record)->not->toBeNull()
        ->and($record->used_at)->toBeNull();
});

it('silently returns for unknown email', function () {
    $this->service->sendResetEmail('nobody@example.com');

    Mail::assertNothingQueued();
});

it('verifies valid token and returns user id', function () {
    $this->service->sendResetEmail($this->user['email']);

    $token = null;
    Mail::assertQueued(ResetPasswordMail::class, function ($mail) use (&$token) {
        $reflection = new ReflectionProperty($mail, 'token');
        $token = $reflection->getValue($mail);

        return true;
    });

    $userId = $this->service->verifyToken($token);

    expect($userId)->toBe($this->user['id']);

    // Token should be marked as used
    $record = DB::table('password_resets')
        ->where('user_id', $this->user['id'])
        ->first();

    expect($record->used_at)->not->toBeNull();
});

it('rejects expired token', function () {
    $this->service->sendResetEmail($this->user['email']);

    DB::table('password_resets')
        ->where('user_id', $this->user['id'])
        ->update(['expires_at' => now()->subHour()]);

    $token = null;
    Mail::assertQueued(ResetPasswordMail::class, function ($mail) use (&$token) {
        $reflection = new ReflectionProperty($mail, 'token');
        $token = $reflection->getValue($mail);

        return true;
    });

    $this->service->verifyToken($token);
})->throws(InvalidTokenException::class);

it('rejects already used token', function () {
    $this->service->sendResetEmail($this->user['email']);

    $token = null;
    Mail::assertQueued(ResetPasswordMail::class, function ($mail) use (&$token) {
        $reflection = new ReflectionProperty($mail, 'token');
        $token = $reflection->getValue($mail);

        return true;
    });

    // Use it once
    $this->service->verifyToken($token);

    // Try to use again
    $this->service->verifyToken($token);
})->throws(InvalidTokenException::class);
