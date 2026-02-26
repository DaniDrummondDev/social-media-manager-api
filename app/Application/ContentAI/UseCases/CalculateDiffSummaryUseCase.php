<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\CalculateDiffSummaryInput;
use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\ContentAI\ValueObjects\DiffSummary;
use App\Domain\Shared\ValueObjects\Uuid;

final class CalculateDiffSummaryUseCase
{
    public function __construct(
        private readonly GenerationFeedbackRepositoryInterface $feedbackRepository,
    ) {}

    public function execute(CalculateDiffSummaryInput $input): void
    {
        $feedback = $this->feedbackRepository->findById(Uuid::fromString($input->feedbackId));

        if ($feedback === null || $feedback->editedOutput === null) {
            return;
        }

        $diffSummary = DiffSummary::compute($feedback->originalOutput, $feedback->editedOutput);
        $updated = $feedback->withDiffSummary($diffSummary);

        $this->feedbackRepository->update($updated);
    }
}
