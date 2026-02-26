<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

use App\Domain\AIIntelligence\Exceptions\InvalidEngagementPatternException;

final readonly class EngagementPattern
{
    /**
     * @param  array<string>  $bestContentTypes
     */
    private function __construct(
        public int $avgLikes,
        public int $avgComments,
        public int $avgShares,
        public array $bestContentTypes,
    ) {}

    /**
     * @param  array<string>  $bestContentTypes
     */
    public static function create(
        int $avgLikes,
        int $avgComments,
        int $avgShares,
        array $bestContentTypes,
    ): self {
        if ($avgLikes < 0 || $avgComments < 0 || $avgShares < 0) {
            throw new InvalidEngagementPatternException('Engagement pattern values must be non-negative.');
        }

        return new self($avgLikes, $avgComments, $avgShares, $bestContentTypes);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            avgLikes: (int) $data['avg_likes'],
            avgComments: (int) $data['avg_comments'],
            avgShares: (int) $data['avg_shares'],
            bestContentTypes: (array) $data['best_content_types'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'avg_likes' => $this->avgLikes,
            'avg_comments' => $this->avgComments,
            'avg_shares' => $this->avgShares,
            'best_content_types' => $this->bestContentTypes,
        ];
    }
}
