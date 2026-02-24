<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\Exceptions\InvalidTokenException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    Mail::fake();
    $this->service = app(EmailVerificationServiceInterface::class);
    $this->user = $this->createUserInDb();
});

it('sends verification email and stores token hash', function () {
    $this->service->sendVerificationEmail($this->user['id'], $this->user['email']);

    Mail::assertQueued(\App\Infrastructure\Identity\Notifications\VerifyEmailMail::class);

    $record = DB::table('email_verifications')
        ->where('user_id', $this->user['id'])
        ->first();

    expect($record)->not->toBeNull()
        ->and($record->token_hash)->toBeString()
        ->and(strlen($record->token_hash))->toBe(64);
});

it('verifies a valid token and returns user id', function () {
    $this->service->sendVerificationEmail($this->user['id'], $this->user['email']);

    // Extract the token from the mailed notification
    $token = null;
    Mail::assertQueued(\App\Infrastructure\Identity\Notifications\VerifyEmailMail::class, function ($mail) use (&$token) {
        $reflection = new ReflectionProperty($mail, 'token');
        $token = $reflection->getValue($mail);

        return true;
    });

    $userId = $this->service->verifyToken($token);

    expect($userId)->toBe($this->user['id']);

    // Token should be deleted after use
    $record = DB::table('email_verifications')
        ->where('user_id', $this->user['id'])
        ->first();

    expect($record)->toBeNull();
});

it('rejects expired token', function () {
    $this->service->sendVerificationEmail($this->user['id'], $this->user['email']);

    // Expire the token manually
    DB::table('email_verifications')
        ->where('user_id', $this->user['id'])
        ->update(['expires_at' => now()->subHour()]);

    $token = null;
    Mail::assertQueued(\App\Infrastructure\Identity\Notifications\VerifyEmailMail::class, function ($mail) use (&$token) {
        $reflection = new ReflectionProperty($mail, 'token');
        $token = $reflection->getValue($mail);

        return true;
    });

    $this->service->verifyToken($token);
})->throws(InvalidTokenException::class);

it('rejects invalid token', function () {
    $this->service->verifyToken('completely-invalid-token');
})->throws(InvalidTokenException::class);

it('resend invalidates previous token', function () {
    $this->service->sendVerificationEmail($this->user['id'], $this->user['email']);

    $countBefore = DB::table('email_verifications')
        ->where('user_id', $this->user['id'])
        ->count();

    $this->service->sendVerificationEmail($this->user['id'], $this->user['email']);

    $countAfter = DB::table('email_verifications')
        ->where('user_id', $this->user['id'])
        ->count();

    expect($countBefore)->toBe(1)
        ->and($countAfter)->toBe(1);
});
