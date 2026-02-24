<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Contracts;

use DateTimeImmutable;

interface SocialAnalyticsInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getPostMetrics(string $externalPostId): array;

    /**
     * @return array<string, mixed>
     */
    public function getAccountMetrics(DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * @return array<string, mixed>
     */
    public function getFollowerMetrics(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
