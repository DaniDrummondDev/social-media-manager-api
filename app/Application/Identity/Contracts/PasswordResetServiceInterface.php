<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface PasswordResetServiceInterface
{
    /**
     * Generate reset token and send email. No-op if email doesn't exist.
     */
    public function sendResetEmail(string $email): void;

    /**
     * Verify the token and return the userId.
     *
     * @throws \App\Application\Identity\Exceptions\InvalidTokenException
     */
    public function verifyToken(string $token): string;

    public function invalidateTokensForUser(string $userId): void;
}
