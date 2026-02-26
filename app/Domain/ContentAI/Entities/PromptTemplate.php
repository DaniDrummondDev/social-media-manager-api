<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Entities;

use App\Domain\ContentAI\Events\PromptPerformanceCalculated;
use App\Domain\ContentAI\Events\PromptTemplateCreated;
use App\Domain\ContentAI\ValueObjects\PerformanceScore;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class PromptTemplate
{
    private const int MIN_USES_FOR_AUTO_SELECTION = 20;

    /**
     * @param  array<string>  $variables
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public ?Uuid $organizationId,
        public string $generationType,
        public string $version,
        public string $name,
        public string $systemPrompt,
        public string $userPromptTemplate,
        public array $variables,
        public bool $isActive,
        public bool $isDefault,
        public ?PerformanceScore $performanceScore,
        public int $totalUses,
        public int $totalAccepted,
        public int $totalEdited,
        public int $totalRejected,
        public ?Uuid $createdBy,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>  $variables
     */
    public static function create(
        ?Uuid $organizationId,
        string $generationType,
        string $version,
        string $name,
        string $systemPrompt,
        string $userPromptTemplate,
        array $variables,
        bool $isDefault,
        Uuid $createdBy,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            generationType: $generationType,
            version: $version,
            name: $name,
            systemPrompt: $systemPrompt,
            userPromptTemplate: $userPromptTemplate,
            variables: $variables,
            isActive: true,
            isDefault: $isDefault,
            performanceScore: null,
            totalUses: 0,
            totalAccepted: 0,
            totalEdited: 0,
            totalRejected: 0,
            createdBy: $createdBy,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new PromptTemplateCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) ($organizationId ?? Uuid::fromString('00000000-0000-0000-0000-000000000000')),
                    userId: (string) $createdBy,
                    generationType: $generationType,
                    version: $version,
                ),
            ],
        );
    }

    /**
     * @param  array<string>  $variables
     */
    public static function reconstitute(
        Uuid $id,
        ?Uuid $organizationId,
        string $generationType,
        string $version,
        string $name,
        string $systemPrompt,
        string $userPromptTemplate,
        array $variables,
        bool $isActive,
        bool $isDefault,
        ?PerformanceScore $performanceScore,
        int $totalUses,
        int $totalAccepted,
        int $totalEdited,
        int $totalRejected,
        ?Uuid $createdBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            generationType: $generationType,
            version: $version,
            name: $name,
            systemPrompt: $systemPrompt,
            userPromptTemplate: $userPromptTemplate,
            variables: $variables,
            isActive: $isActive,
            isDefault: $isDefault,
            performanceScore: $performanceScore,
            totalUses: $totalUses,
            totalAccepted: $totalAccepted,
            totalEdited: $totalEdited,
            totalRejected: $totalRejected,
            createdBy: $createdBy,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function recordUsage(string $feedbackAction): self
    {
        $accepted = $this->totalAccepted + ($feedbackAction === 'accepted' ? 1 : 0);
        $edited = $this->totalEdited + ($feedbackAction === 'edited' ? 1 : 0);
        $rejected = $this->totalRejected + ($feedbackAction === 'rejected' ? 1 : 0);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            version: $this->version,
            name: $this->name,
            systemPrompt: $this->systemPrompt,
            userPromptTemplate: $this->userPromptTemplate,
            variables: $this->variables,
            isActive: $this->isActive,
            isDefault: $this->isDefault,
            performanceScore: $this->performanceScore,
            totalUses: $this->totalUses + 1,
            totalAccepted: $accepted,
            totalEdited: $edited,
            totalRejected: $rejected,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function recalculatePerformance(string $userId): self
    {
        $score = PerformanceScore::calculate(
            totalUses: $this->totalUses,
            totalAccepted: $this->totalAccepted,
            totalEdited: $this->totalEdited,
        );

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            version: $this->version,
            name: $this->name,
            systemPrompt: $this->systemPrompt,
            userPromptTemplate: $this->userPromptTemplate,
            variables: $this->variables,
            isActive: $this->isActive,
            isDefault: $this->isDefault,
            performanceScore: $score,
            totalUses: $this->totalUses,
            totalAccepted: $this->totalAccepted,
            totalEdited: $this->totalEdited,
            totalRejected: $this->totalRejected,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new PromptPerformanceCalculated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) ($this->organizationId ?? Uuid::fromString('00000000-0000-0000-0000-000000000000')),
                    userId: $userId,
                    performanceScore: $score->value,
                    totalUses: $this->totalUses,
                ),
            ],
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            version: $this->version,
            name: $this->name,
            systemPrompt: $this->systemPrompt,
            userPromptTemplate: $this->userPromptTemplate,
            variables: $this->variables,
            isActive: false,
            isDefault: false,
            performanceScore: $this->performanceScore,
            totalUses: $this->totalUses,
            totalAccepted: $this->totalAccepted,
            totalEdited: $this->totalEdited,
            totalRejected: $this->totalRejected,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function isEligibleForAutoSelection(): bool
    {
        return $this->isActive
            && $this->totalUses >= self::MIN_USES_FOR_AUTO_SELECTION
            && $this->performanceScore !== null
            && $this->performanceScore->isEligibleForAutoSelection();
    }

    public function isSystemTemplate(): bool
    {
        return $this->organizationId === null;
    }
}
