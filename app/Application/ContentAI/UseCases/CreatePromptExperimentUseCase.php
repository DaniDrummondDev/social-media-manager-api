<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\CreatePromptExperimentInput;
use App\Application\ContentAI\DTOs\PromptExperimentOutput;
use App\Domain\ContentAI\Contracts\PromptExperimentRepositoryInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\ContentAI\Entities\PromptExperiment;
use App\Domain\Shared\ValueObjects\Uuid;
use DomainException;

final class CreatePromptExperimentUseCase
{
    public function __construct(
        private readonly PromptExperimentRepositoryInterface $experimentRepository,
        private readonly PromptTemplateRepositoryInterface $templateRepository,
    ) {}

    public function execute(CreatePromptExperimentInput $input): PromptExperimentOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $variantAId = Uuid::fromString($input->variantAId);
        $variantBId = Uuid::fromString($input->variantBId);

        // RN-ALL-24: max 1 running experiment per (org, generation_type)
        if ($this->experimentRepository->hasRunningExperiment($organizationId, $input->generationType)) {
            throw new DomainException(
                'An experiment is already running for this organization and generation type.',
            );
        }

        // Validate both variant templates exist
        $variantA = $this->templateRepository->findById($variantAId);
        if ($variantA === null) {
            throw new DomainException("Prompt template not found: {$input->variantAId}");
        }

        $variantB = $this->templateRepository->findById($variantBId);
        if ($variantB === null) {
            throw new DomainException("Prompt template not found: {$input->variantBId}");
        }

        $experiment = PromptExperiment::create(
            organizationId: $organizationId,
            generationType: $input->generationType,
            name: $input->name,
            variantAId: $variantAId,
            variantBId: $variantBId,
            trafficSplit: $input->trafficSplit,
            minSampleSize: $input->minSampleSize,
            userId: $input->userId,
        );

        $this->experimentRepository->create($experiment);

        return PromptExperimentOutput::fromEntity($experiment);
    }
}
