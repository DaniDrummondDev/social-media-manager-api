<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Services;

use App\Application\Identity\Contracts\PasswordResetServiceInterface;
use App\Application\Identity\Exceptions\InvalidTokenException;
use App\Infrastructure\Identity\Models\UserModel;
use App\Infrastructure\Identity\Notifications\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class PasswordResetService implements PasswordResetServiceInterface
{
    public function sendResetEmail(string $email): void
    {
        // Check if user exists — if not, silently return (no email enumeration)
        $user = UserModel::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        // Invalidate existing tokens
        $this->invalidateTokensForUser($user->getAttribute('id'));

        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);

        DB::table('password_resets')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getAttribute('id'),
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHour(),
        ]);

        Mail::to($email)->queue(new ResetPasswordMail($token));
    }

    /**
     * @throws InvalidTokenException
     */
    public function verifyToken(string $token): string
    {
        $tokenHash = hash('sha256', $token);

        $record = DB::table('password_resets')
            ->where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();

        if ($record === null) {
            throw new InvalidTokenException('Invalid or expired reset token');
        }

        // Mark as used
        DB::table('password_resets')
            ->where('id', $record->id)
            ->update(['used_at' => now()]);

        return $record->user_id;
    }

    public function invalidateTokensForUser(string $userId): void
    {
        DB::table('password_resets')
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }
}
