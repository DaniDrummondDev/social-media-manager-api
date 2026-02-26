<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\GenerationFeedbackOutput;
use App\Application\ContentAI\DTOs\RecordGenerationFeedbackInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\ContentAI\Entities\GenerationFeedback;
use App\Domain\ContentAI\ValueObjects\FeedbackAction;
use App\Domain\Shared\ValueObjects\Uuid;

final class RecordGenerationFeedbackUseCase
{
    public function __construct(
        private readonly GenerationFeedbackRepositoryInterface $feedbackRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(RecordGenerationFeedbackInput $input): GenerationFeedbackOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $userId = Uuid::fromString($input->userId);
        $generationId = Uuid::fromString($input->generationId);
        $action = FeedbackAction::from($input->action);

        $feedback = GenerationFeedback::create(
            organizationId: $organizationId,
            userId: $userId,
            generationId: $generationId,
            action: $action,
            originalOutput: $input->originalOutput,
            editedOutput: $input->editedOutput,
            contentId: $input->contentId !== null ? Uuid::fromString($input->contentId) : null,
            generationType: $input->generationType,
            timeToDecisionMs: $input->timeToDecisionMs,
        );

        $this->feedbackRepository->create($feedback);

        // Template counter updates handled via GenerationFeedbackRecorded event listener (Sprint 14.3)
        $this->eventDispatcher->dispatch(...$feedback->domainEvents);

        return GenerationFeedbackOutput::fromEntity($feedback);
    }
}
