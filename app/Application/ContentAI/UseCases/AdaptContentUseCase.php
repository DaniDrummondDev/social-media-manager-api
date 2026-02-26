<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\AdaptContentInput;
use App\Application\ContentAI\DTOs\AIGenerationOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\ValueObjects\Uuid;

final class AdaptContentUseCase
{
    public function __construct(
        private readonly TextGeneratorInterface $textGenerator,
        private readonly AIGenerationRepositoryInterface $generationRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(AdaptContentInput $input): AIGenerationOutput
    {
        $result = $this->textGenerator->adaptContent(
            contentId: $input->contentId,
            organizationId: $input->organizationId,
            sourceNetwork: $input->sourceNetwork,
            targetNetworks: $input->targetNetworks,
            preserveTone: $input->preserveTone,
        );

        $generation = AIGeneration::create(
            organizationId: Uuid::fromString($input->organizationId),
            userId: Uuid::fromString($input->userId),
            type: GenerationType::CrossNetworkAdaptation,
            input: [
                'content_id' => $input->contentId,
                'source_network' => $input->sourceNetwork,
                'target_networks' => $input->targetNetworks,
                'preserve_tone' => $input->preserveTone,
            ],
            output: $result->output,
            usage: new AIUsage(
                tokensInput: $result->tokensInput,
                tokensOutput: $result->tokensOutput,
                model: $result->model,
                costEstimate: $result->costEstimate,
                durationMs: $result->durationMs,
            ),
        );

        $this->generationRepository->create($generation);
        $this->eventDispatcher->dispatch(...$generation->domainEvents);

        return AIGenerationOutput::fromEntity($generation);
    }
}
