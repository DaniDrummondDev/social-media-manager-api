<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\ValueObjects;

final readonly class EncryptedToken
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromEncrypted(string $encrypted): self
    {
        return new self($encrypted);
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
