<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\Contracts\SocialAnalyticsFactoryInterface;
use App\Application\Analytics\DTOs\SyncPostMetricsInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Analytics\Entities\ContentMetric;
use App\Domain\Analytics\Entities\ContentMetricSnapshot;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\Repositories\ContentMetricSnapshotRepositoryInterface;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;

final class SyncPostMetricsUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly ContentMetricRepositoryInterface $contentMetricRepository,
        private readonly ContentMetricSnapshotRepositoryInterface $snapshotRepository,
        private readonly SocialAnalyticsFactoryInterface $analyticsFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(SyncPostMetricsInput $input): void
    {
        $postId = Uuid::fromString($input->scheduledPostId);
        $post = $this->scheduledPostRepository->findById($postId);

        if ($post === null) {
            throw new ScheduledPostNotFoundException($input->scheduledPostId);
        }

        if ($post->externalPostId === null) {
            return;
        }

        $account = $this->socialAccountRepository->findById($post->socialAccountId);
        if ($account === null) {
            return;
        }

        $adapter = $this->analyticsFactory->make($account->provider);
        $rawMetrics = $adapter->getPostMetrics($post->externalPostId);

        $existing = $this->contentMetricRepository->findByContentAndAccount(
            $post->contentId,
            $post->socialAccountId,
        );

        if ($existing !== null) {
            $metric = $existing->updateMetrics(
                impressions: (int) ($rawMetrics['impressions'] ?? 0),
                reach: (int) ($rawMetrics['reach'] ?? 0),
                likes: (int) ($rawMetrics['likes'] ?? 0),
                comments: (int) ($rawMetrics['comments'] ?? 0),
                shares: (int) ($rawMetrics['shares'] ?? 0),
                saves: (int) ($rawMetrics['saves'] ?? 0),
                clicks: (int) ($rawMetrics['clicks'] ?? 0),
                views: isset($rawMetrics['views']) ? (int) $rawMetrics['views'] : null,
                watchTimeSeconds: isset($rawMetrics['watch_time_seconds']) ? (int) $rawMetrics['watch_time_seconds'] : null,
                organizationId: (string) $post->organizationId,
                userId: (string) $post->scheduledBy,
            );
        } else {
            $metric = ContentMetric::create(
                contentId: $post->contentId,
                socialAccountId: $post->socialAccountId,
                provider: $account->provider,
                externalPostId: $post->externalPostId,
                impressions: (int) ($rawMetrics['impressions'] ?? 0),
                reach: (int) ($rawMetrics['reach'] ?? 0),
                likes: (int) ($rawMetrics['likes'] ?? 0),
                comments: (int) ($rawMetrics['comments'] ?? 0),
                shares: (int) ($rawMetrics['shares'] ?? 0),
                saves: (int) ($rawMetrics['saves'] ?? 0),
                clicks: (int) ($rawMetrics['clicks'] ?? 0),
                views: isset($rawMetrics['views']) ? (int) $rawMetrics['views'] : null,
                watchTimeSeconds: isset($rawMetrics['watch_time_seconds']) ? (int) $rawMetrics['watch_time_seconds'] : null,
                organizationId: (string) $post->organizationId,
                userId: (string) $post->scheduledBy,
            );
        }

        $this->contentMetricRepository->upsert($metric);

        $snapshot = ContentMetricSnapshot::create($metric->id, $metric);
        $this->snapshotRepository->create($snapshot);

        $this->eventDispatcher->dispatch(...$metric->domainEvents);
    }
}
