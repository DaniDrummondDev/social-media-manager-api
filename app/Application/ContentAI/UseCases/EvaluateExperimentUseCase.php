<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\EvaluateExperimentInput;
use App\Application\ContentAI\DTOs\PromptExperimentOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\PromptExperimentRepositoryInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\Shared\Exceptions\DomainException;

final class EvaluateExperimentUseCase
{
    public function __construct(
        private readonly PromptExperimentRepositoryInterface $experimentRepository,
        private readonly PromptTemplateRepositoryInterface $templateRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(EvaluateExperimentInput $input): PromptExperimentOutput
    {
        $experimentId = Uuid::fromString($input->experimentId);

        $experiment = $this->experimentRepository->findById($experimentId);
        if ($experiment === null) {
            throw new DomainException("Prompt experiment not found: {$input->experimentId}");
        }

        // Evaluate using z-test — returns unchanged experiment if confidence < 0.95
        $evaluated = $experiment->evaluate($input->userId);

        // If experiment was completed, activate winner and deactivate loser
        if ($evaluated->winnerId !== null && $experiment->winnerId === null) {
            $this->activateWinner($evaluated);
        }

        $this->experimentRepository->update($evaluated);
        $this->eventDispatcher->dispatch(...$evaluated->domainEvents);

        return PromptExperimentOutput::fromEntity($evaluated);
    }

    private function activateWinner(
        \App\Domain\ContentAI\Entities\PromptExperiment $experiment,
    ): void {
        $winnerId = $experiment->winnerId;
        $loserId = $winnerId->equals($experiment->variantAId)
            ? $experiment->variantBId
            : $experiment->variantAId;

        // Deactivate the loser template (RN-ALL-28: never deleted)
        $loser = $this->templateRepository->findById($loserId);
        if ($loser !== null) {
            $this->templateRepository->update($loser->deactivate());
        }
    }
}
