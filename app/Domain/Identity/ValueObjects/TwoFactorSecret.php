<?php

declare(strict_types=1);

namespace App\Domain\Identity\ValueObjects;

final readonly class TwoFactorSecret
{
    public function __construct(
        public string $encryptedValue,
    ) {}

    public function equals(self $other): bool
    {
        return $this->encryptedValue === $other->encryptedValue;
    }

    public function __toString(): string
    {
        return $this->encryptedValue;
    }
}
