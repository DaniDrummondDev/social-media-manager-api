<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\DTOs\GenerateEmbeddingInput;
use App\Application\AIIntelligence\DTOs\GenerateEmbeddingOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Events\EmbeddingGenerated;

final class GenerateEmbeddingUseCase
{
    public function __construct(
        private readonly EmbeddingGeneratorInterface $embeddingGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(GenerateEmbeddingInput $input): GenerateEmbeddingOutput
    {
        $embedding = $this->embeddingGenerator->generate($input->text);

        $this->eventDispatcher->dispatch(
            new EmbeddingGenerated(
                aggregateId: $input->entityId,
                organizationId: $input->organizationId,
                userId: $input->userId,
                entityType: $input->entityType,
                entityId: $input->entityId,
                model: $this->embeddingGenerator->getModel(),
            ),
        );

        return new GenerateEmbeddingOutput(
            entityType: $input->entityType,
            entityId: $input->entityId,
            embedding: $embedding,
            dimensions: $this->embeddingGenerator->getDimensions(),
            model: $this->embeddingGenerator->getModel(),
        );
    }
}
