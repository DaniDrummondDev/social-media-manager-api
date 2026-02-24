<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

use App\Domain\Media\Exceptions\InvalidMimeTypeException;

final readonly class MimeType
{
    private const array ALLOWED = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'video/mp4',
        'video/quicktime',
        'video/webm',
    ];

    private const array EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm' => 'webm',
    ];

    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $mimeType): self
    {
        $normalized = strtolower(trim($mimeType));

        if (! in_array($normalized, self::ALLOWED, true)) {
            throw new InvalidMimeTypeException($mimeType);
        }

        return new self($normalized);
    }

    public function mediaType(): MediaType
    {
        return str_starts_with($this->value, 'image/')
            ? MediaType::Image
            : MediaType::Video;
    }

    public function extension(): string
    {
        return self::EXTENSIONS[$this->value];
    }

    public function isImage(): bool
    {
        return $this->mediaType()->isImage();
    }

    public function isVideo(): bool
    {
        return $this->mediaType()->isVideo();
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
