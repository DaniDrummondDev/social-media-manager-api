<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

use App\Domain\Media\Exceptions\FileSizeExceededException;

final readonly class FileSize
{
    public const int MAX_SIMPLE_UPLOAD = 10 * 1024 * 1024; // 10MB

    private function __construct(
        public int $bytes,
    ) {}

    public static function fromBytes(int $bytes): self
    {
        if ($bytes <= 0) {
            throw new FileSizeExceededException('File size must be greater than zero.');
        }

        return new self($bytes);
    }

    public function toKilobytes(): float
    {
        return $this->bytes / 1024;
    }

    public function toMegabytes(): float
    {
        return $this->bytes / (1024 * 1024);
    }

    public function toGigabytes(): float
    {
        return $this->bytes / (1024 * 1024 * 1024);
    }

    public function exceedsLimit(int $maxBytes): bool
    {
        return $this->bytes > $maxBytes;
    }

    public function requiresChunkedUpload(): bool
    {
        return $this->bytes > self::MAX_SIMPLE_UPLOAD;
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->bytes;
    }

    public function __toString(): string
    {
        return (string) $this->bytes;
    }
}
