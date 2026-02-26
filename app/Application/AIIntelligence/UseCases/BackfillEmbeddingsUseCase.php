<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\DTOs\BackfillEmbeddingsInput;
use App\Application\AIIntelligence\DTOs\BackfillEmbeddingsOutput;
use App\Application\AIIntelligence\Exceptions\EmbeddingGenerationFailedException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Events\EmbeddingGenerated;

final class BackfillEmbeddingsUseCase
{
    public function __construct(
        private readonly EmbeddingGeneratorInterface $embeddingGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(BackfillEmbeddingsInput $input): BackfillEmbeddingsOutput
    {
        $texts = array_column($input->items, 'text');

        try {
            $embeddings = $this->embeddingGenerator->generateBatch($texts);
        } catch (\Throwable $e) {
            throw new EmbeddingGenerationFailedException($e->getMessage());
        }

        $successCount = count($embeddings);

        foreach ($input->items as $index => $item) {
            if (isset($embeddings[$index])) {
                $this->eventDispatcher->dispatch(
                    new EmbeddingGenerated(
                        aggregateId: $item['entity_id'],
                        organizationId: $input->organizationId,
                        userId: $input->userId,
                        entityType: $item['entity_type'],
                        entityId: $item['entity_id'],
                        model: $this->embeddingGenerator->getModel(),
                    ),
                );
            }
        }

        return new BackfillEmbeddingsOutput(
            totalItems: count($input->items),
            successCount: $successCount,
            failedCount: count($input->items) - $successCount,
            model: $this->embeddingGenerator->getModel(),
        );
    }
}
