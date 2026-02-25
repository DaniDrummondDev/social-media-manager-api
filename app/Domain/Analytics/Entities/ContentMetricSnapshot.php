<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entities;

use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class ContentMetricSnapshot
{
    public function __construct(
        public Uuid $id,
        public Uuid $contentMetricId,
        public int $impressions,
        public int $reach,
        public int $likes,
        public int $comments,
        public int $shares,
        public int $saves,
        public int $clicks,
        public ?int $views,
        public ?int $watchTimeSeconds,
        public float $engagementRate,
        public DateTimeImmutable $capturedAt,
    ) {}

    public static function create(
        Uuid $contentMetricId,
        ContentMetric $metric,
    ): self {
        return new self(
            id: Uuid::generate(),
            contentMetricId: $contentMetricId,
            impressions: $metric->impressions,
            reach: $metric->reach,
            likes: $metric->likes,
            comments: $metric->comments,
            shares: $metric->shares,
            saves: $metric->saves,
            clicks: $metric->clicks,
            views: $metric->views,
            watchTimeSeconds: $metric->watchTimeSeconds,
            engagementRate: $metric->engagementRate,
            capturedAt: new DateTimeImmutable,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $contentMetricId,
        int $impressions,
        int $reach,
        int $likes,
        int $comments,
        int $shares,
        int $saves,
        int $clicks,
        ?int $views,
        ?int $watchTimeSeconds,
        float $engagementRate,
        DateTimeImmutable $capturedAt,
    ): self {
        return new self(
            id: $id,
            contentMetricId: $contentMetricId,
            impressions: $impressions,
            reach: $reach,
            likes: $likes,
            comments: $comments,
            shares: $shares,
            saves: $saves,
            clicks: $clicks,
            views: $views,
            watchTimeSeconds: $watchTimeSeconds,
            engagementRate: $engagementRate,
            capturedAt: $capturedAt,
        );
    }
}
