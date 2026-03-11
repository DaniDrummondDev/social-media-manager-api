<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\AIGenerationOutput;
use App\Application\ContentAI\DTOs\GenerateDescriptionInput;
use App\Application\ContentAI\Services\BriefContextResolver;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\ValueObjects\Uuid;

final class GenerateDescriptionUseCase
{
    public function __construct(
        private readonly TextGeneratorInterface $textGenerator,
        private readonly AIGenerationRepositoryInterface $generationRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly BriefContextResolver $briefContextResolver,
    ) {}

    public function execute(GenerateDescriptionInput $input): AIGenerationOutput
    {
        $topic = $this->briefContextResolver->resolve(
            $input->generationMode,
            $input->campaignId,
            $input->organizationId,
            $input->topic,
        );

        $result = $this->textGenerator->generateDescription(
            topic: $topic,
            socialNetwork: $input->socialNetwork,
            tone: $input->tone,
            keywords: $input->keywords,
            language: $input->language,
        );

        $generation = AIGeneration::create(
            organizationId: Uuid::fromString($input->organizationId),
            userId: Uuid::fromString($input->userId),
            type: GenerationType::Description,
            input: [
                'topic' => $input->topic,
                'social_network' => $input->socialNetwork,
                'tone' => $input->tone,
                'keywords' => $input->keywords,
                'language' => $input->language,
                'generation_mode' => $input->generationMode,
                'campaign_id' => $input->campaignId,
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
