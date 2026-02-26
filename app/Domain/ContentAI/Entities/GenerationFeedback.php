<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Entities;

use App\Domain\ContentAI\Events\GenerationEdited;
use App\Domain\ContentAI\Events\GenerationFeedbackRecorded;
use App\Domain\ContentAI\Exceptions\InvalidFeedbackException;
use App\Domain\ContentAI\ValueObjects\DiffSummary;
use App\Domain\ContentAI\ValueObjects\FeedbackAction;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class GenerationFeedback
{
    /**
     * @param  array<string, mixed>  $originalOutput
     * @param  array<string, mixed>|null  $editedOutput
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $userId,
        public Uuid $generationId,
        public FeedbackAction $action,
        public array $originalOutput,
        public ?array $editedOutput,
        public ?DiffSummary $diffSummary,
        public ?Uuid $contentId,
        public string $generationType,
        public ?int $timeToDecisionMs,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $originalOutput
     * @param  array<string, mixed>|null  $editedOutput
     */
    public static function create(
        Uuid $organizationId,
        Uuid $userId,
        Uuid $generationId,
        FeedbackAction $action,
        array $originalOutput,
        ?array $editedOutput,
        ?Uuid $contentId,
        string $generationType,
        ?int $timeToDecisionMs,
    ): self {
        if ($action->requiresEditedOutput() && $editedOutput === null) {
            throw new InvalidFeedbackException('Edited output is required when action is "edited".');
        }

        if (! $action->requiresEditedOutput()) {
            $editedOutput = null;
        }

        $id = Uuid::generate();

        $events = [
            new GenerationFeedbackRecorded(
                aggregateId: (string) $id,
                organizationId: (string) $organizationId,
                userId: (string) $userId,
                generationId: (string) $generationId,
                action: $action->value,
                generationType: $generationType,
            ),
        ];

        if ($action === FeedbackAction::Edited) {
            $events[] = new GenerationEdited(
                aggregateId: (string) $id,
                organizationId: (string) $organizationId,
                userId: (string) $userId,
                generationId: (string) $generationId,
                changeRatio: 0.0, // Diff is computed asynchronously by CalculateDiffSummaryJob
            );
        }

        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            generationId: $generationId,
            action: $action,
            originalOutput: $originalOutput,
            editedOutput: $editedOutput,
            diffSummary: null, // Computed asynchronously
            contentId: $contentId,
            generationType: $generationType,
            timeToDecisionMs: $timeToDecisionMs,
            createdAt: new DateTimeImmutable,
            domainEvents: $events,
        );
    }

    /**
     * @param  array<string, mixed>  $originalOutput
     * @param  array<string, mixed>|null  $editedOutput
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $userId,
        Uuid $generationId,
        FeedbackAction $action,
        array $originalOutput,
        ?array $editedOutput,
        ?DiffSummary $diffSummary,
        ?Uuid $contentId,
        string $generationType,
        ?int $timeToDecisionMs,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            generationId: $generationId,
            action: $action,
            originalOutput: $originalOutput,
            editedOutput: $editedOutput,
            diffSummary: $diffSummary,
            contentId: $contentId,
            generationType: $generationType,
            timeToDecisionMs: $timeToDecisionMs,
            createdAt: $createdAt,
        );
    }

    public function withDiffSummary(DiffSummary $diffSummary): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            generationId: $this->generationId,
            action: $this->action,
            originalOutput: $this->originalOutput,
            editedOutput: $this->editedOutput,
            diffSummary: $diffSummary,
            contentId: $this->contentId,
            generationType: $this->generationType,
            timeToDecisionMs: $this->timeToDecisionMs,
            createdAt: $this->createdAt,
        );
    }

    public function isEdited(): bool
    {
        return $this->action === FeedbackAction::Edited;
    }

    public function isAccepted(): bool
    {
        return $this->action === FeedbackAction::Accepted;
    }

    public function isRejected(): bool
    {
        return $this->action === FeedbackAction::Rejected;
    }
}
