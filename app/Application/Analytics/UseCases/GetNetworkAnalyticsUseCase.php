<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\GetNetworkAnalyticsInput;
use App\Application\Analytics\DTOs\GetNetworkAnalyticsOutput;
use App\Domain\Analytics\Repositories\AccountMetricRepositoryInterface;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use DateTimeImmutable;

final class GetNetworkAnalyticsUseCase
{
    public function __construct(
        private readonly ContentMetricRepositoryInterface $contentMetricRepository,
        private readonly AccountMetricRepositoryInterface $accountMetricRepository,
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
    ) {}

    public function execute(GetNetworkAnalyticsInput $input): GetNetworkAnalyticsOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        try {
            $provider = SocialProvider::from($input->provider);
        } catch (\ValueError) {
            throw new \App\Application\Shared\Exceptions\ApplicationException(
                message: "Provedor '{$input->provider}' não é válido.",
                errorCode: 'INVALID_PROVIDER',
            );
        }

        $period = $this->resolvePeriod($input);
        $previousPeriod = $period->previousPeriod();

        $accounts = $this->socialAccountRepository->findByOrganizationId($organizationId);
        $providerAccount = null;
        foreach ($accounts as $account) {
            if ($account->provider === $provider) {
                $providerAccount = $account;
                break;
            }
        }

        $accountData = [];
        $followersTrend = [];

        if ($providerAccount !== null) {
            $accountSummary = $this->accountMetricRepository->getAccountSummary(
                $providerAccount->id,
                $period,
            );
            $accountData = [
                'id' => (string) $providerAccount->id,
                'username' => $providerAccount->username,
                'display_name' => $providerAccount->displayName,
                ...$accountSummary,
            ];
            $followersTrend = $this->accountMetricRepository->getFollowersTrend(
                $providerAccount->id,
                $period,
            );
        }

        $contentMetrics = $this->contentMetricRepository->getAggregatedMetrics($organizationId, $period);
        $comparison = $this->contentMetricRepository->getAggregatedMetrics($organizationId, $previousPeriod);
        $topContents = $this->contentMetricRepository->findTopByEngagement($organizationId, $period, 5);
        $bestPostingTimes = $this->contentMetricRepository->getBestPostingTimes(
            $organizationId,
            $provider->value,
        );

        $topContentsData = array_map(fn ($metric) => [
            'content_id' => (string) $metric->contentId,
            'impressions' => $metric->impressions,
            'reach' => $metric->reach,
            'likes' => $metric->likes,
            'comments' => $metric->comments,
            'shares' => $metric->shares,
            'saves' => $metric->saves,
            'engagement_rate' => $metric->engagementRate,
        ], $topContents);

        return new GetNetworkAnalyticsOutput(
            provider: $input->provider,
            period: $input->period,
            account: $accountData,
            contentMetrics: $contentMetrics,
            comparison: $this->calculateComparison($contentMetrics, $comparison),
            top5Contents: $topContentsData,
            bestPostingTimes: $bestPostingTimes,
            followersTrend: $followersTrend,
        );
    }

    private function resolvePeriod(GetNetworkAnalyticsInput $input): MetricPeriod
    {
        if ($input->period === 'custom' && $input->from !== null && $input->to !== null) {
            return MetricPeriod::custom(
                new DateTimeImmutable($input->from),
                new DateTimeImmutable($input->to),
            );
        }

        return MetricPeriod::fromPreset($input->period);
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function calculateComparison(array $current, array $previous): array
    {
        $metrics = ['impressions', 'reach', 'likes', 'comments', 'shares', 'saves', 'clicks', 'engagement_rate'];
        $result = [];

        foreach ($metrics as $metric) {
            $currentVal = (float) ($current[$metric] ?? 0);
            $previousVal = (float) ($previous[$metric] ?? 0);

            $result[$metric] = [
                'current' => $currentVal,
                'previous' => $previousVal,
                'change' => $previousVal > 0
                    ? round(($currentVal - $previousVal) / $previousVal * 100, 2)
                    : 0.0,
            ];
        }

        return $result;
    }
}
