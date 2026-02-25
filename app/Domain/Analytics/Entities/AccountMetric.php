<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entities;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use DateTimeImmutable;

final readonly class AccountMetric
{
    public function __construct(
        public Uuid $id,
        public Uuid $socialAccountId,
        public SocialProvider $provider,
        public DateTimeImmutable $date,
        public int $followersCount,
        public int $followersGained,
        public int $followersLost,
        public int $profileViews,
        public int $reach,
        public int $impressions,
        public DateTimeImmutable $syncedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        Uuid $socialAccountId,
        SocialProvider $provider,
        DateTimeImmutable $date,
        int $followersCount,
        int $followersGained,
        int $followersLost,
        int $profileViews,
        int $reach,
        int $impressions,
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            id: Uuid::generate(),
            socialAccountId: $socialAccountId,
            provider: $provider,
            date: $date,
            followersCount: $followersCount,
            followersGained: $followersGained,
            followersLost: $followersLost,
            profileViews: $profileViews,
            reach: $reach,
            impressions: $impressions,
            syncedAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $socialAccountId,
        SocialProvider $provider,
        DateTimeImmutable $date,
        int $followersCount,
        int $followersGained,
        int $followersLost,
        int $profileViews,
        int $reach,
        int $impressions,
        DateTimeImmutable $syncedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            socialAccountId: $socialAccountId,
            provider: $provider,
            date: $date,
            followersCount: $followersCount,
            followersGained: $followersGained,
            followersLost: $followersLost,
            profileViews: $profileViews,
            reach: $reach,
            impressions: $impressions,
            syncedAt: $syncedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function updateMetrics(
        int $followersCount,
        int $followersGained,
        int $followersLost,
        int $profileViews,
        int $reach,
        int $impressions,
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            date: $this->date,
            followersCount: $followersCount,
            followersGained: $followersGained,
            followersLost: $followersLost,
            profileViews: $profileViews,
            reach: $reach,
            impressions: $impressions,
            syncedAt: $now,
            createdAt: $this->createdAt,
            updatedAt: $now,
        );
    }
}
