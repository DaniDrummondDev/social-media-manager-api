<?php

declare(strict_types=1);

namespace App\Domain\Identity\ValueObjects;

use App\Domain\Identity\Exceptions\InvalidEmailException;

final readonly class Email
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $email): self
    {
        $normalized = strtolower(trim($email));

        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($email);
        }

        return new self($normalized);
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
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
