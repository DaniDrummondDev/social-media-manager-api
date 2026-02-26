<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

use App\Domain\AIIntelligence\Exceptions\InvalidContentFingerprintException;

final readonly class ContentFingerprint
{
    /**
     * @param  array<string>  $hashtagPatterns
     * @param  array<string, float>  $toneDistribution
     */
    private function __construct(
        public int $avgLength,
        public array $hashtagPatterns,
        public array $toneDistribution,
        public float $postingFrequency,
    ) {}

    /**
     * @param  array<string>  $hashtagPatterns
     * @param  array<string, float>  $toneDistribution
     */
    public static function create(
        int $avgLength,
        array $hashtagPatterns,
        array $toneDistribution,
        float $postingFrequency,
    ): self {
        if ($avgLength < 0) {
            throw new InvalidContentFingerprintException('Average length must be non-negative.');
        }

        if ($postingFrequency < 0) {
            throw new InvalidContentFingerprintException('Posting frequency must be non-negative.');
        }

        return new self($avgLength, $hashtagPatterns, $toneDistribution, $postingFrequency);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            avgLength: (int) $data['avg_length'],
            hashtagPatterns: (array) $data['hashtag_patterns'],
            toneDistribution: (array) $data['tone_distribution'],
            postingFrequency: (float) $data['posting_frequency'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'avg_length' => $this->avgLength,
            'hashtag_patterns' => $this->hashtagPatterns,
            'tone_distribution' => $this->toneDistribution,
            'posting_frequency' => $this->postingFrequency,
        ];
    }
}
