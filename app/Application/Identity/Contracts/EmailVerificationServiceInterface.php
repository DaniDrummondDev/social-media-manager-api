<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface EmailVerificationServiceInterface
{
    public function sendVerificationEmail(string $userId, string $email): void;

    /**
     * Verify the token and return the userId.
     *
     * @throws \App\Application\Identity\Exceptions\InvalidTokenException
     */
    public function verifyToken(string $token): string;
}
