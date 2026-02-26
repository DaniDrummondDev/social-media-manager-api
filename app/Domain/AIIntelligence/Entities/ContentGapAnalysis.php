<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\ContentGapsIdentified;
use App\Domain\AIIntelligence\Exceptions\GapAnalysisExpiredException;
use App\Domain\AIIntelligence\Exceptions\InvalidGapAnalysisStatusTransitionException;
use App\Domain\AIIntelligence\ValueObjects\GapAnalysisStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class ContentGapAnalysis
{
    /**
     * @param  array<string>  $competitorQueryIds
     * @param  array<array{topic: string, frequency: int, avg_engagement: float}>  $ourTopics
     * @param  array<array{topic: string, source_competitor: string, frequency: int, avg_engagement: float}>  $competitorTopics
     * @param  array<array{topic: string, opportunity_score: int, competitor_count: int, recommendation: string}>  $gaps
     * @param  array<array{topic: string, reason: string, suggested_content_type: string, estimated_impact: string}>  $opportunities
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public array $competitorQueryIds,
        public DateTimeImmutable $analysisPeriodStart,
        public DateTimeImmutable $analysisPeriodEnd,
        public array $ourTopics,
        public array $competitorTopics,
        public array $gaps,
        public array $opportunities,
        public GapAnalysisStatus $status,
        public DateTimeImmutable $generatedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>  $competitorQueryIds
     */
    public static function create(
        Uuid $organizationId,
        array $competitorQueryIds,
        DateTimeImmutable $analysisPeriodStart,
        DateTimeImmutable $analysisPeriodEnd,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            competitorQueryIds: $competitorQueryIds,
            analysisPeriodStart: $analysisPeriodStart,
            analysisPeriodEnd: $analysisPeriodEnd,
            ourTopics: [],
            competitorTopics: [],
            gaps: [],
            opportunities: [],
            status: GapAnalysisStatus::Generating,
            generatedAt: $now,
            expiresAt: $now->modify('+7 days'),
            createdAt: $now,
        );
    }

    /**
     * @param  array<string>  $competitorQueryIds
     * @param  array<array{topic: string, frequency: int, avg_engagement: float}>  $ourTopics
     * @param  array<array{topic: string, source_competitor: string, frequency: int, avg_engagement: float}>  $competitorTopics
     * @param  array<array{topic: string, opportunity_score: int, competitor_count: int, recommendation: string}>  $gaps
     * @param  array<array{topic: string, reason: string, suggested_content_type: string, estimated_impact: string}>  $opportunities
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        array $competitorQueryIds,
        DateTimeImmutable $analysisPeriodStart,
        DateTimeImmutable $analysisPeriodEnd,
        array $ourTopics,
        array $competitorTopics,
        array $gaps,
        array $opportunities,
        GapAnalysisStatus $status,
        DateTimeImmutable $generatedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            competitorQueryIds: $competitorQueryIds,
            analysisPeriodStart: $analysisPeriodStart,
            analysisPeriodEnd: $analysisPeriodEnd,
            ourTopics: $ourTopics,
            competitorTopics: $competitorTopics,
            gaps: $gaps,
            opportunities: $opportunities,
            status: $status,
            generatedAt: $generatedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
        );
    }

    /**
     * @param  array<array{topic: string, frequency: int, avg_engagement: float}>  $ourTopics
     * @param  array<array{topic: string, source_competitor: string, frequency: int, avg_engagement: float}>  $competitorTopics
     * @param  array<array{topic: string, opportunity_score: int, competitor_count: int, recommendation: string}>  $gaps
     * @param  array<array{topic: string, reason: string, suggested_content_type: string, estimated_impact: string}>  $opportunities
     */
    public function complete(
        array $ourTopics,
        array $competitorTopics,
        array $gaps,
        array $opportunities,
        string $userId,
    ): self {
        $this->assertCanTransitionTo(GapAnalysisStatus::Generated);

        $periodDays = (int) $this->analysisPeriodStart->diff($this->analysisPeriodEnd)->days;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            competitorQueryIds: $this->competitorQueryIds,
            analysisPeriodStart: $this->analysisPeriodStart,
            analysisPeriodEnd: $this->analysisPeriodEnd,
            ourTopics: $ourTopics,
            competitorTopics: $competitorTopics,
            gaps: $gaps,
            opportunities: $opportunities,
            status: GapAnalysisStatus::Generated,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            domainEvents: [
                new ContentGapsIdentified(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    competitorQueryCount: count($this->competitorQueryIds),
                    gapCount: count($gaps),
                    opportunityCount: count($opportunities),
                    analysisPeriodDays: $periodDays,
                ),
            ],
        );
    }

    public function markExpired(): self
    {
        if ($this->status === GapAnalysisStatus::Expired) {
            throw new GapAnalysisExpiredException;
        }

        $this->assertCanTransitionTo(GapAnalysisStatus::Expired);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            competitorQueryIds: $this->competitorQueryIds,
            analysisPeriodStart: $this->analysisPeriodStart,
            analysisPeriodEnd: $this->analysisPeriodEnd,
            ourTopics: $this->ourTopics,
            competitorTopics: $this->competitorTopics,
            gaps: $this->gaps,
            opportunities: $this->opportunities,
            status: GapAnalysisStatus::Expired,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable;
    }

    /**
     * @return array<array{topic: string, reason: string, suggested_content_type: string, estimated_impact: string}>
     */
    public function getActionableOpportunities(int $minScore = 50): array
    {
        return array_values(array_filter(
            $this->gaps,
            fn (array $gap): bool => ($gap['opportunity_score'] ?? 0) >= $minScore,
        ));
    }

    private function assertCanTransitionTo(GapAnalysisStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidGapAnalysisStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }
}
