<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Entities;

use App\Domain\ContentAI\Events\PromptExperimentCompleted;
use App\Domain\ContentAI\Events\PromptExperimentStarted;
use App\Domain\ContentAI\Exceptions\InvalidExperimentStatusTransitionException;
use App\Domain\ContentAI\ValueObjects\ExperimentStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class PromptExperiment
{
    private const float CONFIDENCE_THRESHOLD = 0.95;

    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $generationType,
        public string $name,
        public ExperimentStatus $status,
        public Uuid $variantAId,
        public Uuid $variantBId,
        public float $trafficSplit,
        public int $minSampleSize,
        public int $variantAUses,
        public int $variantAAccepted,
        public int $variantBUses,
        public int $variantBAccepted,
        public ?Uuid $winnerId,
        public ?float $confidenceLevel,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        string $generationType,
        string $name,
        Uuid $variantAId,
        Uuid $variantBId,
        float $trafficSplit,
        int $minSampleSize,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            generationType: $generationType,
            name: $name,
            status: ExperimentStatus::Draft,
            variantAId: $variantAId,
            variantBId: $variantBId,
            trafficSplit: $trafficSplit,
            minSampleSize: $minSampleSize,
            variantAUses: 0,
            variantAAccepted: 0,
            variantBUses: 0,
            variantBAccepted: 0,
            winnerId: null,
            confidenceLevel: null,
            startedAt: null,
            completedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $generationType,
        string $name,
        ExperimentStatus $status,
        Uuid $variantAId,
        Uuid $variantBId,
        float $trafficSplit,
        int $minSampleSize,
        int $variantAUses,
        int $variantAAccepted,
        int $variantBUses,
        int $variantBAccepted,
        ?Uuid $winnerId,
        ?float $confidenceLevel,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            generationType: $generationType,
            name: $name,
            status: $status,
            variantAId: $variantAId,
            variantBId: $variantBId,
            trafficSplit: $trafficSplit,
            minSampleSize: $minSampleSize,
            variantAUses: $variantAUses,
            variantAAccepted: $variantAAccepted,
            variantBUses: $variantBUses,
            variantBAccepted: $variantBAccepted,
            winnerId: $winnerId,
            confidenceLevel: $confidenceLevel,
            startedAt: $startedAt,
            completedAt: $completedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function start(string $userId): self
    {
        $this->assertCanTransitionTo(ExperimentStatus::Running);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            name: $this->name,
            status: ExperimentStatus::Running,
            variantAId: $this->variantAId,
            variantBId: $this->variantBId,
            trafficSplit: $this->trafficSplit,
            minSampleSize: $this->minSampleSize,
            variantAUses: $this->variantAUses,
            variantAAccepted: $this->variantAAccepted,
            variantBUses: $this->variantBUses,
            variantBAccepted: $this->variantBAccepted,
            winnerId: null,
            confidenceLevel: null,
            startedAt: new DateTimeImmutable,
            completedAt: null,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new PromptExperimentStarted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    generationType: $this->generationType,
                    variantAId: (string) $this->variantAId,
                    variantBId: (string) $this->variantBId,
                ),
            ],
        );
    }

    public function recordVariantUsage(bool $isVariantA, bool $accepted): self
    {
        if ($this->status !== ExperimentStatus::Running) {
            return $this;
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            name: $this->name,
            status: $this->status,
            variantAId: $this->variantAId,
            variantBId: $this->variantBId,
            trafficSplit: $this->trafficSplit,
            minSampleSize: $this->minSampleSize,
            variantAUses: $this->variantAUses + ($isVariantA ? 1 : 0),
            variantAAccepted: $this->variantAAccepted + ($isVariantA && $accepted ? 1 : 0),
            variantBUses: $this->variantBUses + (! $isVariantA ? 1 : 0),
            variantBAccepted: $this->variantBAccepted + (! $isVariantA && $accepted ? 1 : 0),
            winnerId: null,
            confidenceLevel: null,
            startedAt: $this->startedAt,
            completedAt: null,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function hasMinimumSamples(): bool
    {
        return $this->variantAUses >= $this->minSampleSize
            && $this->variantBUses >= $this->minSampleSize;
    }

    /**
     * Two-proportion z-test for statistical significance.
     */
    public function calculateConfidence(): ?float
    {
        if (! $this->hasMinimumSamples()) {
            return null;
        }

        $pA = $this->variantAUses > 0 ? $this->variantAAccepted / $this->variantAUses : 0;
        $pB = $this->variantBUses > 0 ? $this->variantBAccepted / $this->variantBUses : 0;

        $nA = $this->variantAUses;
        $nB = $this->variantBUses;

        $pPool = ($this->variantAAccepted + $this->variantBAccepted) / ($nA + $nB);
        $se = sqrt($pPool * (1 - $pPool) * (1 / $nA + 1 / $nB));

        if ($se == 0.0) {
            return null;
        }

        $z = abs($pA - $pB) / $se;

        // Approximate confidence level from z-score using normal CDF
        return $this->normalCdf($z);
    }

    public function evaluate(string $userId): self
    {
        $confidence = $this->calculateConfidence();

        if ($confidence === null || $confidence < self::CONFIDENCE_THRESHOLD) {
            return $this;
        }

        $this->assertCanTransitionTo(ExperimentStatus::Completed);

        $pA = $this->variantAAccepted / $this->variantAUses;
        $pB = $this->variantBAccepted / $this->variantBUses;
        $winnerId = $pA >= $pB ? $this->variantAId : $this->variantBId;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            name: $this->name,
            status: ExperimentStatus::Completed,
            variantAId: $this->variantAId,
            variantBId: $this->variantBId,
            trafficSplit: $this->trafficSplit,
            minSampleSize: $this->minSampleSize,
            variantAUses: $this->variantAUses,
            variantAAccepted: $this->variantAAccepted,
            variantBUses: $this->variantBUses,
            variantBAccepted: $this->variantBAccepted,
            winnerId: $winnerId,
            confidenceLevel: $confidence,
            startedAt: $this->startedAt,
            completedAt: new DateTimeImmutable,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new PromptExperimentCompleted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    winnerId: (string) $winnerId,
                    confidenceLevel: $confidence,
                ),
            ],
        );
    }

    public function cancel(): self
    {
        $this->assertCanTransitionTo(ExperimentStatus::Canceled);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            name: $this->name,
            status: ExperimentStatus::Canceled,
            variantAId: $this->variantAId,
            variantBId: $this->variantBId,
            trafficSplit: $this->trafficSplit,
            minSampleSize: $this->minSampleSize,
            variantAUses: $this->variantAUses,
            variantAAccepted: $this->variantAAccepted,
            variantBUses: $this->variantBUses,
            variantBAccepted: $this->variantBAccepted,
            winnerId: null,
            confidenceLevel: null,
            startedAt: $this->startedAt,
            completedAt: new DateTimeImmutable,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    /**
     * Select which variant to serve based on traffic split.
     * Returns true for variant A, false for variant B.
     */
    public function selectVariant(): bool
    {
        return mt_rand(1, 100) / 100 <= $this->trafficSplit;
    }

    public function getAcceptanceRateA(): float
    {
        return $this->variantAUses > 0
            ? round($this->variantAAccepted / $this->variantAUses * 100, 2)
            : 0.0;
    }

    public function getAcceptanceRateB(): float
    {
        return $this->variantBUses > 0
            ? round($this->variantBAccepted / $this->variantBUses * 100, 2)
            : 0.0;
    }

    private function assertCanTransitionTo(ExperimentStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidExperimentStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }

    /**
     * Approximate normal CDF using Abramowitz and Stegun formula.
     * Returns P(Z <= z) for the standard normal distribution.
     */
    private function normalCdf(float $z): float
    {
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        $sign = $z < 0 ? -1 : 1;
        $z = abs($z) / sqrt(2);

        $t = 1.0 / (1.0 + $p * $z);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$z * $z);

        // Two-tailed confidence = 1 - 2*(1 - CDF(|z|))
        $oneTailed = 0.5 * (1.0 + $sign * $y);

        return 1.0 - 2.0 * (1.0 - $oneTailed);
    }
}
