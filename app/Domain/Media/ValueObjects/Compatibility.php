<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

final readonly class Compatibility
{
    private function __construct(
        public bool $instagramFeed,
        public bool $instagramStory,
        public bool $instagramReel,
        public bool $tiktok,
        public bool $youtube,
        public bool $youtubeShort,
    ) {}

    public static function calculate(
        MimeType $mimeType,
        FileSize $fileSize,
        ?Dimensions $dimensions,
        ?int $durationSeconds,
    ): self {
        return new self(
            instagramFeed: self::checkInstagramFeed($mimeType, $fileSize, $dimensions, $durationSeconds),
            instagramStory: self::checkInstagramStory($mimeType, $fileSize, $dimensions, $durationSeconds),
            instagramReel: self::checkInstagramReel($mimeType, $fileSize, $dimensions, $durationSeconds),
            tiktok: self::checkTikTok($mimeType, $fileSize, $durationSeconds),
            youtube: self::checkYouTube($mimeType, $fileSize, $durationSeconds),
            youtubeShort: self::checkYouTubeShort($mimeType, $fileSize, $dimensions, $durationSeconds),
        );
    }

    public static function none(): self
    {
        return new self(
            instagramFeed: false,
            instagramStory: false,
            instagramReel: false,
            tiktok: false,
            youtube: false,
            youtubeShort: false,
        );
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'instagram_feed' => $this->instagramFeed,
            'instagram_story' => $this->instagramStory,
            'instagram_reel' => $this->instagramReel,
            'tiktok' => $this->tiktok,
            'youtube' => $this->youtube,
            'youtube_short' => $this->youtubeShort,
        ];
    }

    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    // Instagram Feed: image (JPEG/PNG/WebP) ≤8MB, aspect 4:5–1.91:1; video (MP4) ≤100MB, 3–60s
    private static function checkInstagramFeed(
        MimeType $mimeType,
        FileSize $fileSize,
        ?Dimensions $dimensions,
        ?int $durationSeconds,
    ): bool {
        if ($mimeType->isImage()) {
            if (! in_array($mimeType->value, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                return false;
            }
            if ($fileSize->exceedsLimit(8 * 1024 * 1024)) {
                return false;
            }
            if ($dimensions !== null) {
                $ratio = $dimensions->aspectRatio();
                if ($ratio < 0.8 || $ratio > 1.91) { // 4:5 = 0.8, 1.91:1 = 1.91
                    return false;
                }
            }

            return true;
        }

        if ($mimeType->value !== 'video/mp4') {
            return false;
        }
        if ($fileSize->exceedsLimit(100 * 1024 * 1024)) {
            return false;
        }
        if ($durationSeconds !== null && ($durationSeconds < 3 || $durationSeconds > 60)) {
            return false;
        }

        return true;
    }

    // Instagram Story: image ≤8MB, 9:16; video ≤100MB, ≤60s, 9:16
    private static function checkInstagramStory(
        MimeType $mimeType,
        FileSize $fileSize,
        ?Dimensions $dimensions,
        ?int $durationSeconds,
    ): bool {
        if (! self::isVerticalAspect($dimensions)) {
            return false;
        }

        if ($mimeType->isImage()) {
            return ! $fileSize->exceedsLimit(8 * 1024 * 1024);
        }

        if ($mimeType->value !== 'video/mp4') {
            return false;
        }
        if ($fileSize->exceedsLimit(100 * 1024 * 1024)) {
            return false;
        }

        return $durationSeconds === null || $durationSeconds <= 60;
    }

    // Instagram Reel: video MP4, ≤100MB, 3–90s, 9:16
    private static function checkInstagramReel(
        MimeType $mimeType,
        FileSize $fileSize,
        ?Dimensions $dimensions,
        ?int $durationSeconds,
    ): bool {
        if ($mimeType->value !== 'video/mp4') {
            return false;
        }
        if ($fileSize->exceedsLimit(100 * 1024 * 1024)) {
            return false;
        }
        if ($durationSeconds !== null && ($durationSeconds < 3 || $durationSeconds > 90)) {
            return false;
        }

        return self::isVerticalAspect($dimensions);
    }

    // TikTok: video MP4/WebM, ≤287MB, 3–180s, 9:16
    private static function checkTikTok(
        MimeType $mimeType,
        FileSize $fileSize,
        ?int $durationSeconds,
    ): bool {
        if (! in_array($mimeType->value, ['video/mp4', 'video/webm'], true)) {
            return false;
        }
        if ($fileSize->exceedsLimit(287 * 1024 * 1024)) {
            return false;
        }

        return $durationSeconds === null || ($durationSeconds >= 3 && $durationSeconds <= 180);
    }

    // YouTube: video MP4/QuickTime, ≤128GB, ≤12h
    private static function checkYouTube(
        MimeType $mimeType,
        FileSize $fileSize,
        ?int $durationSeconds,
    ): bool {
        if (! in_array($mimeType->value, ['video/mp4', 'video/quicktime'], true)) {
            return false;
        }
        if ($fileSize->exceedsLimit(128 * 1024 * 1024 * 1024)) {
            return false;
        }

        return $durationSeconds === null || $durationSeconds <= 43200; // 12h
    }

    // YouTube Short: video MP4, ≤60s, 9:16
    private static function checkYouTubeShort(
        MimeType $mimeType,
        FileSize $fileSize,
        ?Dimensions $dimensions,
        ?int $durationSeconds,
    ): bool {
        if ($mimeType->value !== 'video/mp4') {
            return false;
        }
        if ($fileSize->exceedsLimit(128 * 1024 * 1024 * 1024)) {
            return false;
        }
        if ($durationSeconds !== null && $durationSeconds > 60) {
            return false;
        }

        return self::isVerticalAspect($dimensions);
    }

    // 9:16 aspect ratio check with tolerance
    private static function isVerticalAspect(?Dimensions $dimensions): bool
    {
        if ($dimensions === null) {
            return true; // unknown dimensions — don't block
        }

        $ratio = $dimensions->aspectRatio();

        return $ratio >= 0.5 && $ratio <= 0.625; // 9:16 ≈ 0.5625, ±tolerance
    }
}
