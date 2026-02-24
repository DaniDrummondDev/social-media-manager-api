<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Services;

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\Exceptions\InvalidTokenException;
use App\Infrastructure\Identity\Notifications\VerifyEmailMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class EmailVerificationService implements EmailVerificationServiceInterface
{
    public function sendVerificationEmail(string $userId, string $email): void
    {
        // Invalidate any existing tokens for this user
        DB::table('email_verifications')
            ->where('user_id', $userId)
            ->delete();

        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);

        DB::table('email_verifications')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours(24),
        ]);

        Mail::to($email)->queue(new VerifyEmailMail($token));
    }

    /**
     * @throws InvalidTokenException
     */
    public function verifyToken(string $token): string
    {
        $tokenHash = hash('sha256', $token);

        $record = DB::table('email_verifications')
            ->where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            throw new InvalidTokenException('Invalid or expired verification token');
        }

        // Delete the used token
        DB::table('email_verifications')
            ->where('id', $record->id)
            ->delete();

        return $record->user_id;
    }
}
