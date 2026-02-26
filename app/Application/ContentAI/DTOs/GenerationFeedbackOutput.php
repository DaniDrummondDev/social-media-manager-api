<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

use App\Domain\ContentAI\Entities\GenerationFeedback;

final readonly class GenerationFeedbackOutput
{
    /**
     * @param  array<string, mixed>  $originalOutput
     * @param  array<string, mixed>|null  $editedOutput
     * @param  array<string, mixed>|null  $diffSummary
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $userId,
        public string $generationId,
        public string $action,
        public array $originalOutput,
        public ?array $editedOutput,
        public ?array $diffSummary,
        public ?string $contentId,
        public string $generationType,
        public ?int $timeToDecisionMs,
        public string $createdAt,
    ) {}

    public static function fromEntity(GenerationFeedback $feedback): self
    {
        return new self(
            id: (string) $feedback->id,
            organizationId: (string) $feedback->organizationId,
            userId: (string) $feedback->userId,
            generationId: (string) $feedback->generationId,
            action: $feedback->action->value,
            originalOutput: $feedback->originalOutput,
            editedOutput: $feedback->editedOutput,
            diffSummary: $feedback->diffSummary?->toArray(),
            contentId: $feedback->contentId !== null ? (string) $feedback->contentId : null,
            generationType: $feedback->generationType,
            timeToDecisionMs: $feedback->timeToDecisionMs,
            createdAt: $feedback->createdAt->format('c'),
        );
    }
}
