<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\CalendarItemsAccepted;
use App\Domain\AIIntelligence\Events\CalendarSuggestionGenerated;
use App\Domain\AIIntelligence\Exceptions\CalendarSuggestionExpiredException;
use App\Domain\AIIntelligence\Exceptions\InvalidSuggestionStatusTransitionException;
use App\Domain\AIIntelligence\ValueObjects\CalendarItem;
use App\Domain\AIIntelligence\ValueObjects\SuggestionStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class CalendarSuggestion
{
    /**
     * @param  array<CalendarItem>  $suggestions
     * @param  array<string, mixed>  $basedOn
     * @param  array<int>|null  $acceptedItems
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public DateTimeImmutable $periodStart,
        public DateTimeImmutable $periodEnd,
        public array $suggestions,
        public array $basedOn,
        public SuggestionStatus $status,
        public ?array $acceptedItems,
        public DateTimeImmutable $generatedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            suggestions: [],
            basedOn: [],
            status: SuggestionStatus::Generating,
            acceptedItems: null,
            generatedAt: $now,
            expiresAt: $now->modify('+7 days'),
            createdAt: $now,
            domainEvents: [
                new CalendarSuggestionGenerated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    periodStart: $periodStart->format('Y-m-d'),
                    periodEnd: $periodEnd->format('Y-m-d'),
                ),
            ],
        );
    }

    /**
     * @param  array<CalendarItem>  $suggestions
     * @param  array<string, mixed>  $basedOn
     * @param  array<int>|null  $acceptedItems
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        array $suggestions,
        array $basedOn,
        SuggestionStatus $status,
        ?array $acceptedItems,
        DateTimeImmutable $generatedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            suggestions: $suggestions,
            basedOn: $basedOn,
            status: $status,
            acceptedItems: $acceptedItems,
            generatedAt: $generatedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
        );
    }

    /**
     * @param  array<CalendarItem>  $suggestions
     * @param  array<string, mixed>  $basedOn
     */
    public function complete(array $suggestions, array $basedOn): self
    {
        $this->assertCanTransitionTo(SuggestionStatus::Generated);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            suggestions: $suggestions,
            basedOn: $basedOn,
            status: SuggestionStatus::Generated,
            acceptedItems: $this->acceptedItems,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
        );
    }

    public function markReviewed(): self
    {
        $this->assertCanTransitionTo(SuggestionStatus::Reviewed);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            suggestions: $this->suggestions,
            basedOn: $this->basedOn,
            status: SuggestionStatus::Reviewed,
            acceptedItems: $this->acceptedItems,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
        );
    }

    /**
     * @param  array<int>  $acceptedIndexes
     */
    public function acceptItems(array $acceptedIndexes, string $userId): self
    {
        if ($this->isExpired()) {
            throw new CalendarSuggestionExpiredException;
        }

        $this->assertCanTransitionTo(SuggestionStatus::Accepted);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            suggestions: $this->suggestions,
            basedOn: $this->basedOn,
            status: SuggestionStatus::Accepted,
            acceptedItems: $acceptedIndexes,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            domainEvents: [
                new CalendarItemsAccepted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    acceptedCount: count($acceptedIndexes),
                    totalCount: count($this->suggestions),
                ),
            ],
        );
    }

    public function markExpired(): self
    {
        $this->assertCanTransitionTo(SuggestionStatus::Expired);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            suggestions: $this->suggestions,
            basedOn: $this->basedOn,
            status: SuggestionStatus::Expired,
            acceptedItems: $this->acceptedItems,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable;
    }

    private function assertCanTransitionTo(SuggestionStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidSuggestionStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }
}
