<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\Contracts\SocialAnalyticsFactoryInterface;
use App\Application\Analytics\DTOs\SyncAccountMetricsInput;
use App\Domain\Analytics\Entities\AccountMetric;
use App\Domain\Analytics\Repositories\AccountMetricRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use DateTimeImmutable;

final class SyncAccountMetricsUseCase
{
    public function __construct(
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly AccountMetricRepositoryInterface $accountMetricRepository,
        private readonly SocialAnalyticsFactoryInterface $analyticsFactory,
    ) {}

    public function execute(SyncAccountMetricsInput $input): void
    {
        $accountId = Uuid::fromString($input->socialAccountId);
        $account = $this->socialAccountRepository->findById($accountId);

        if ($account === null) {
            return;
        }

        $adapter = $this->analyticsFactory->make($account->provider);

        $today = new DateTimeImmutable('today');
        $accountMetrics = $adapter->getAccountMetrics($today, $today);
        $followerMetrics = $adapter->getFollowerMetrics($today, $today);

        $existing = $this->accountMetricRepository->getLatest($accountId);

        if ($existing !== null && $existing->date->format('Y-m-d') === $today->format('Y-m-d')) {
            $metric = $existing->updateMetrics(
                followersCount: (int) ($followerMetrics['followers_count'] ?? 0),
                followersGained: (int) ($followerMetrics['followers_gained'] ?? 0),
                followersLost: (int) ($followerMetrics['followers_lost'] ?? 0),
                profileViews: (int) ($accountMetrics['profile_views'] ?? 0),
                reach: (int) ($accountMetrics['reach'] ?? 0),
                impressions: (int) ($accountMetrics['impressions'] ?? 0),
            );
        } else {
            $metric = AccountMetric::create(
                socialAccountId: $accountId,
                provider: $account->provider,
                date: $today,
                followersCount: (int) ($followerMetrics['followers_count'] ?? 0),
                followersGained: (int) ($followerMetrics['followers_gained'] ?? 0),
                followersLost: (int) ($followerMetrics['followers_lost'] ?? 0),
                profileViews: (int) ($accountMetrics['profile_views'] ?? 0),
                reach: (int) ($accountMetrics['reach'] ?? 0),
                impressions: (int) ($accountMetrics['impressions'] ?? 0),
            );
        }

        $this->accountMetricRepository->upsert($metric);
    }
}
