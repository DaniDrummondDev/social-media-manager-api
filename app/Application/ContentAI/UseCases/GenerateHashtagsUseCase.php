<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\AIGenerationOutput;
use App\Application\ContentAI\DTOs\GenerateHashtagsInput;
use App\Application\ContentAI\Services\BriefContextResolver;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\ValueObjects\Uuid;

final class GenerateHashtagsUseCase
{
    public function __construct(
        private readonly TextGeneratorInterface $textGenerator,
        private readonly AIGenerationRepositoryInterface $generationRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly BriefContextResolver $briefContextResolver,
    ) {}

    public function execute(GenerateHashtagsInput $input): AIGenerationOutput
    {
        $topic = $this->briefContextResolver->resolve(
            $input->generationMode,
            $input->campaignId,
            $input->organizationId,
            $input->topic,
        );

        $result = $this->textGenerator->generateHashtags(
            topic: $topic,
            niche: $input->niche,
            socialNetwork: $input->socialNetwork,
        );

        $generation = AIGeneration::create(
            organizationId: Uuid::fromString($input->organizationId),
            userId: Uuid::fromString($input->userId),
            type: GenerationType::Hashtags,
            input: [
                'topic' => $input->topic,
                'niche' => $input->niche,
                'social_network' => $input->socialNetwork,
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
