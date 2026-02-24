<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';

    public function isImage(): bool
    {
        return $this === self::Image;
    }

    public function isVideo(): bool
    {
        return $this === self::Video;
    }
}
