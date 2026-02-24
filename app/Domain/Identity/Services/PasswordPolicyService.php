<?php

declare(strict_types=1);

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Exceptions\WeakPasswordException;

final class PasswordPolicyService
{
    public function validate(string $password, ?string $email = null): void
    {
        if (strlen($password) < 8) {
            throw new WeakPasswordException('minimum 8 characters required');
        }

        if (! preg_match('/[A-Z]/', $password)) {
            throw new WeakPasswordException('at least one uppercase letter required');
        }

        if (! preg_match('/[a-z]/', $password)) {
            throw new WeakPasswordException('at least one lowercase letter required');
        }

        if (! preg_match('/[0-9]/', $password)) {
            throw new WeakPasswordException('at least one number required');
        }

        if (! preg_match('/[!@#$%^&*()\-_=+]/', $password)) {
            throw new WeakPasswordException('at least one special character required (!@#$%^&*()-_=+)');
        }

        if ($email !== null && strtolower($password) === strtolower($email)) {
            throw new WeakPasswordException('password cannot be equal to email');
        }
    }
}
