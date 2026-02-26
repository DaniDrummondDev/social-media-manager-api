<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

use App\Domain\AIIntelligence\Exceptions\InvalidPredictionBreakdownException;

final readonly class PredictionBreakdown
{
    private function __construct(
        public int $contentSimilarity,
        public int $timing,
        public int $hashtags,
        public int $length,
        public int $mediaType,
    ) {}

    public static function create(
        int $contentSimilarity,
        int $timing,
        int $hashtags,
        int $length,
        int $mediaType,
    ): self {
        foreach ([$contentSimilarity, $timing, $hashtags, $length, $mediaType] as $score) {
            if ($score < 0 || $score > 100) {
                throw new InvalidPredictionBreakdownException;
            }
        }

        return new self($contentSimilarity, $timing, $hashtags, $length, $mediaType);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            contentSimilarity: (int) $data['content_similarity'],
            timing: (int) $data['timing'],
            hashtags: (int) $data['hashtags'],
            length: (int) $data['length'],
            mediaType: (int) $data['media_type'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content_similarity' => $this->contentSimilarity,
            'timing' => $this->timing,
            'hashtags' => $this->hashtags,
            'length' => $this->length,
            'media_type' => $this->mediaType,
        ];
    }
}
