<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\GetContentAnalyticsInput;
use App\Application\Analytics\DTOs\GetContentAnalyticsOutput;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\Repositories\ContentMetricSnapshotRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Exceptions\ContentNotFoundException;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetContentAnalyticsUseCase
{
    public function __construct(
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly ContentMetricRepositoryInterface $contentMetricRepository,
        private readonly ContentMetricSnapshotRepositoryInterface $snapshotRepository,
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
    ) {}

    public function execute(GetContentAnalyticsInput $input): GetContentAnalyticsOutput
    {
        $contentId = Uuid::fromString($input->contentId);

        $content = $this->contentRepository->findById($contentId);
        if ($content === null || (string) $content->organizationId !== $input->organizationId) {
            throw new ContentNotFoundException($input->contentId);
        }

        $scheduledPosts = $this->scheduledPostRepository->findByContentId($contentId);
        $lastSyncedAt = null;
        $networks = [];

        foreach ($scheduledPosts as $post) {
            $metric = $this->contentMetricRepository->findByContentAndAccount(
                $contentId,
                $post->socialAccountId,
            );

            if ($metric === null) {
                continue;
            }

            $evolution = $post->publishedAt !== null
                ? $this->snapshotRepository->getEvolution($metric->id, $post->publishedAt)
                : [];

            $networks[] = [
                'social_account_id' => (string) $post->socialAccountId,
                'provider' => $metric->provider->value,
                'external_post_id' => $metric->externalPostId,
                'impressions' => $metric->impressions,
                'reach' => $metric->reach,
                'likes' => $metric->likes,
                'comments' => $metric->comments,
                'shares' => $metric->shares,
                'saves' => $metric->saves,
                'clicks' => $metric->clicks,
                'views' => $metric->views,
                'watch_time_seconds' => $metric->watchTimeSeconds,
                'engagement_rate' => $metric->engagementRate,
                'synced_at' => $metric->syncedAt->format('c'),
                'evolution' => $evolution,
            ];

            if ($lastSyncedAt === null || $metric->syncedAt > $lastSyncedAt) {
                $lastSyncedAt = $metric->syncedAt;
            }
        }

        return new GetContentAnalyticsOutput(
            contentId: $input->contentId,
            title: $content->title,
            campaignName: null,
            publishedAt: null,
            networks: $networks,
            lastSyncedAt: $lastSyncedAt?->format('c'),
        );
    }
}
