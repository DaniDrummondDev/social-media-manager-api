<?php

declare(strict_types=1);

namespace App\Domain\Organization\ValueObjects;

use App\Domain\Organization\Exceptions\InvalidSlugException;

final readonly class OrganizationSlug
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $slug): self
    {
        $normalized = strtolower(trim($slug));

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,98}[a-z0-9])?$/', $normalized)) {
            throw new InvalidSlugException($slug);
        }

        return new self($normalized);
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
