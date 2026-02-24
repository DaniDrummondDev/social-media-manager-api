<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\DomainException;
use Illuminate\Support\Str;

final readonly class Uuid
{
    private function __construct(
        public string $value,
    ) {}

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public static function fromString(string $value): self
    {
        if (! Str::isUuid($value)) {
            throw new DomainException("Invalid UUID: {$value}");
        }

        return new self($value);
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
