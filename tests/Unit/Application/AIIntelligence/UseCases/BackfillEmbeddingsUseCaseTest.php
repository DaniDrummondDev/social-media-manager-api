<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\DTOs\BackfillEmbeddingsInput;
use App\Application\AIIntelligence\DTOs\BackfillEmbeddingsOutput;
use App\Application\AIIntelligence\Exceptions\EmbeddingGenerationFailedException;
use App\Application\AIIntelligence\UseCases\BackfillEmbeddingsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Events\EmbeddingGenerated;

beforeEach(function () {
    $this->embeddingGenerator = Mockery::mock(EmbeddingGeneratorInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->useCase = new BackfillEmbeddingsUseCase($this->embeddingGenerator, $this->eventDispatcher);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('backfills embeddings for multiple items', function () {
    $items = [
        ['entity_type' => 'content', 'entity_id' => 'id-1', 'text' => 'Text one'],
        ['entity_type' => 'content', 'entity_id' => 'id-2', 'text' => 'Text two'],
    ];

    $embeddings = [
        array_fill(0, 1536, 0.1),
        array_fill(0, 1536, 0.2),
    ];

    $this->embeddingGenerator->shouldReceive('generateBatch')
        ->once()
        ->with(['Text one', 'Text two'])
        ->andReturn($embeddings);

    $this->embeddingGenerator->shouldReceive('getModel')->andReturn('stub');

    $this->eventDispatcher->shouldReceive('dispatch')
        ->twice()
        ->withArgs(fn (EmbeddingGenerated $event) => in_array($event->entityId, ['id-1', 'id-2']));

    $input = new BackfillEmbeddingsInput(
        organizationId: $this->orgId,
        items: $items,
        userId: 'user-1',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(BackfillEmbeddingsOutput::class)
        ->and($output->totalItems)->toBe(2)
        ->and($output->successCount)->toBe(2)
        ->and($output->failedCount)->toBe(0);
});

it('throws when batch generation fails', function () {
    $this->embeddingGenerator->shouldReceive('generateBatch')
        ->once()
        ->andThrow(new RuntimeException('API error'));

    $input = new BackfillEmbeddingsInput(
        organizationId: $this->orgId,
        items: [['entity_type' => 'content', 'entity_id' => 'id-1', 'text' => 'Text']],
        userId: 'user-1',
    );

    $this->useCase->execute($input);
})->throws(EmbeddingGenerationFailedException::class);
