<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\BrandSafetyBlocked;
use App\Domain\AIIntelligence\Events\BrandSafetyChecked;
use App\Domain\AIIntelligence\Exceptions\SafetyCheckAlreadyCompletedException;
use App\Domain\AIIntelligence\ValueObjects\SafetyCheckResult;
use App\Domain\AIIntelligence\ValueObjects\SafetyStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class BrandSafetyCheck
{
    /**
     * @param  array<SafetyCheckResult>  $checks
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $contentId,
        public ?string $provider,
        public SafetyStatus $overallStatus,
        public ?int $overallScore,
        public array $checks,
        public ?string $modelUsed,
        public ?int $tokensInput,
        public ?int $tokensOutput,
        public ?DateTimeImmutable $checkedAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $contentId,
        ?string $provider,
    ): self {
        return new self(
            id: Uuid::generate(),
            organizationId: $organizationId,
            contentId: $contentId,
            provider: $provider,
            overallStatus: SafetyStatus::Pending,
            overallScore: null,
            checks: [],
            modelUsed: null,
            tokensInput: null,
            tokensOutput: null,
            checkedAt: null,
            createdAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<SafetyCheckResult>  $checks
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $contentId,
        ?string $provider,
        SafetyStatus $overallStatus,
        ?int $overallScore,
        array $checks,
        ?string $modelUsed,
        ?int $tokensInput,
        ?int $tokensOutput,
        ?DateTimeImmutable $checkedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            provider: $provider,
            overallStatus: $overallStatus,
            overallScore: $overallScore,
            checks: $checks,
            modelUsed: $modelUsed,
            tokensInput: $tokensInput,
            tokensOutput: $tokensOutput,
            checkedAt: $checkedAt,
            createdAt: $createdAt,
        );
    }

    /**
     * @param  array<SafetyCheckResult>  $checks
     */
    public function complete(
        int $score,
        array $checks,
        ?string $modelUsed,
        ?int $tokensInput,
        ?int $tokensOutput,
        string $userId,
    ): self {
        if ($this->overallStatus->isFinal()) {
            throw new SafetyCheckAlreadyCompletedException;
        }

        $overallStatus = self::resolveOverallStatus($checks);
        $now = new DateTimeImmutable;

        $events = [
            new BrandSafetyChecked(
                aggregateId: (string) $this->id,
                organizationId: (string) $this->organizationId,
                userId: $userId,
                contentId: (string) $this->contentId,
                overallStatus: $overallStatus->value,
                score: $score,
            ),
        ];

        if ($overallStatus === SafetyStatus::Blocked) {
            $blockedCategories = array_map(
                fn (SafetyCheckResult $check) => $check->category->value,
                array_filter($checks, fn (SafetyCheckResult $check) => $check->status === SafetyStatus::Blocked),
            );

            $events[] = new BrandSafetyBlocked(
                aggregateId: (string) $this->id,
                organizationId: (string) $this->organizationId,
                userId: $userId,
                contentId: (string) $this->contentId,
                blockedCategories: array_values($blockedCategories),
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            provider: $this->provider,
            overallStatus: $overallStatus,
            overallScore: $score,
            checks: $checks,
            modelUsed: $modelUsed,
            tokensInput: $tokensInput,
            tokensOutput: $tokensOutput,
            checkedAt: $now,
            createdAt: $this->createdAt,
            domainEvents: $events,
        );
    }

    public function isBlocked(): bool
    {
        return $this->overallStatus === SafetyStatus::Blocked;
    }

    public function hasWarnings(): bool
    {
        return $this->overallStatus === SafetyStatus::Warning;
    }

    /**
     * @param  array<SafetyCheckResult>  $checks
     */
    private static function resolveOverallStatus(array $checks): SafetyStatus
    {
        $hasBlocked = false;
        $hasWarning = false;

        foreach ($checks as $check) {
            if ($check->status === SafetyStatus::Blocked) {
                $hasBlocked = true;
            }

            if ($check->status === SafetyStatus::Warning) {
                $hasWarning = true;
            }
        }

        if ($hasBlocked) {
            return SafetyStatus::Blocked;
        }

        if ($hasWarning) {
            return SafetyStatus::Warning;
        }

        return SafetyStatus::Passed;
    }
}
