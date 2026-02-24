<?php

declare(strict_types=1);

namespace App\Application\Media\Contracts;

interface MediaMetadataExtractorInterface
{
    /**
     * @return array{width: int, height: int}|null
     */
    public function extractDimensions(string $filePath): ?array;

    /**
     * @return int|null Duration in seconds
     */
    public function extractDuration(string $filePath): ?int;
}
