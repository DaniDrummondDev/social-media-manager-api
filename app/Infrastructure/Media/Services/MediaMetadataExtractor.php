<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Services;

use App\Application\Media\Contracts\MediaMetadataExtractorInterface;

final class MediaMetadataExtractor implements MediaMetadataExtractorInterface
{
    /**
     * @return array{width: int, height: int}|null
     */
    public function extractDimensions(string $filePath): ?array
    {
        // TODO: Implement with getimagesize() for images, ffprobe for videos
        return null;
    }

    public function extractDuration(string $filePath): ?int
    {
        // TODO: Implement with ffprobe for videos
        return null;
    }
}
