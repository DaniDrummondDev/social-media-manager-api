<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Adapters;

use App\Domain\SocialAccount\Contracts\SocialAnalyticsInterface;
use DateTimeImmutable;
use RuntimeException;

final class YouTubeAnalytics implements SocialAnalyticsInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getPostMetrics(string $externalPostId): array
    {
        throw new RuntimeException('YouTubeAnalytics::getPostMetrics() is not implemented yet.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountMetrics(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        throw new RuntimeException('YouTubeAnalytics::getAccountMetrics() is not implemented yet.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getFollowerMetrics(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        throw new RuntimeException('YouTubeAnalytics::getFollowerMetrics() is not implemented yet.');
    }
}
