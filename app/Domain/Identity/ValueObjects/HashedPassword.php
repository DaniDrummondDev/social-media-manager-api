<?php

declare(strict_types=1);

namespace App\Domain\Identity\ValueObjects;

final readonly class HashedPassword
{
    private function __construct(
        public string $hash,
    ) {}

    public static function fromPlainText(string $plainText): self
    {
        return new self(password_hash($plainText, PASSWORD_BCRYPT, ['cost' => 12]));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function verify(string $plainText): bool
    {
        return password_verify($plainText, $this->hash);
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
