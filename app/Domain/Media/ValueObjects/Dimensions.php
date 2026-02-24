<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

use App\Domain\Media\Exceptions\InvalidDimensionsException;

final readonly class Dimensions
{
    private function __construct(
        public int $width,
        public int $height,
    ) {}

    public static function create(int $width, int $height): self
    {
        if ($width <= 0 || $height <= 0) {
            throw new InvalidDimensionsException($width, $height);
        }

        return new self($width, $height);
    }

    public function aspectRatio(): float
    {
        return $this->width / $this->height;
    }

    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }

    public function isLandscape(): bool
    {
        return $this->width > $this->height;
    }

    public function isPortrait(): bool
    {
        return $this->height > $this->width;
    }

    public function equals(self $other): bool
    {
        return $this->width === $other->width && $this->height === $other->height;
    }

    public function __toString(): string
    {
        return "{$this->width}x{$this->height}";
    }
}
