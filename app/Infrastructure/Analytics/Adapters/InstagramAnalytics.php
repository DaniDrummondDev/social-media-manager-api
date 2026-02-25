<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Adapters;

use App\Domain\SocialAccount\Contracts\SocialAnalyticsInterface;
use DateTimeImmutable;
use RuntimeException;

final class InstagramAnalytics implements SocialAnalyticsInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getPostMetrics(string $externalPostId): array
    {
        throw new RuntimeException('InstagramAnalytics::getPostMetrics() is not implemented yet.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountMetrics(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        throw new RuntimeException('InstagramAnalytics::getAccountMetrics() is not implemented yet.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getFollowerMetrics(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        throw new RuntimeException('InstagramAnalytics::getFollowerMetrics() is not implemented yet.');
    }
}
