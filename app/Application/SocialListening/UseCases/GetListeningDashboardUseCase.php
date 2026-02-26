<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\GetListeningDashboardInput;
use App\Application\SocialListening\DTOs\ListeningDashboardOutput;
use App\Application\SocialListening\DTOs\PlatformBreakdownOutput;
use App\Application\SocialListening\DTOs\SentimentTrendOutput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;
use DateTimeImmutable;

final class GetListeningDashboardUseCase
{
    public function __construct(
        private readonly MentionRepositoryInterface $mentionRepository,
    ) {}

    public function execute(GetListeningDashboardInput $input): ListeningDashboardOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        [$from, $to] = $this->parsePeriod($input->period, $input->from, $input->to);

        $totalMentions = $this->mentionRepository->countByOrganizationInPeriod(
            organizationId: $organizationId,
            from: $from,
            to: $to,
            queryId: $input->queryId,
        );

        $sentimentCounts = $this->mentionRepository->getSentimentCounts(
            organizationId: $organizationId,
            from: $from,
            to: $to,
            queryId: $input->queryId,
        );

        $sentimentBreakdown = SentimentBreakdown::create(
            positive: $sentimentCounts['positive'] ?? 0,
            neutral: $sentimentCounts['neutral'] ?? 0,
            negative: $sentimentCounts['negative'] ?? 0,
        );

        $sentimentTrend = $this->mentionRepository->getSentimentTrend(
            organizationId: $organizationId,
            from: $from,
            to: $to,
            queryId: $input->queryId,
        );

        $mentionsTrend = array_map(
            fn (array $item) => new SentimentTrendOutput(
                date: $item['date'],
                positive: $item['positive'] ?? 0,
                neutral: $item['neutral'] ?? 0,
                negative: $item['negative'] ?? 0,
                total: $item['total'] ?? 0,
            ),
            $sentimentTrend,
        );

        $topAuthors = $this->mentionRepository->getTopAuthors(
            organizationId: $organizationId,
            from: $from,
            to: $to,
            queryId: $input->queryId,
        );

        $platformBreakdownRaw = $this->mentionRepository->getPlatformBreakdown(
            organizationId: $organizationId,
            from: $from,
            to: $to,
            queryId: $input->queryId,
        );

        $platformBreakdown = array_map(
            fn (array $item) => new PlatformBreakdownOutput(
                platform: $item['platform'],
                count: $item['count'],
                percentage: $totalMentions > 0 ? round(($item['count'] / $totalMentions) * 100, 2) : 0.0,
            ),
            $platformBreakdownRaw,
        );

        return new ListeningDashboardOutput(
            totalMentions: $totalMentions,
            sentimentBreakdown: $sentimentBreakdown->toArray(),
            mentionsTrend: $mentionsTrend,
            topAuthors: $topAuthors,
            topKeywords: [],
            platformBreakdown: $platformBreakdown,
            period: $input->period,
        );
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function parsePeriod(string $period, ?string $from, ?string $to): array
    {
        if ($from !== null && $to !== null) {
            return [
                new DateTimeImmutable($from),
                new DateTimeImmutable($to),
            ];
        }

        $now = new DateTimeImmutable();

        $days = match ($period) {
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        return [
            $now->modify("-{$days} days"),
            $now,
        ];
    }
}
