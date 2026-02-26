<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\CalculatePromptPerformanceInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CalculatePromptPerformanceUseCase
{
    public function __construct(
        private readonly PromptTemplateRepositoryInterface $templateRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Recalculate performance_score for all active prompt templates.
     * Formula (RN-ALL-22): (accepted + edited × 0.7) / total_uses × 100
     */
    public function execute(CalculatePromptPerformanceInput $input): void
    {
        $organizationId = $input->organizationId !== null
            ? Uuid::fromString($input->organizationId)
            : null;

        $templates = $this->templateRepository->findAllActive(
            organizationId: $organizationId,
            generationType: $input->generationType,
        );

        foreach ($templates as $template) {
            if ($template->totalUses === 0) {
                continue;
            }

            $updated = $template->recalculatePerformance($input->userId);
            $this->templateRepository->update($updated);
            $this->eventDispatcher->dispatch(...$updated->domainEvents);
        }
    }
}
