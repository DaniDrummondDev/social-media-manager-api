<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\DTOs\GenerateEmbeddingInput;
use App\Application\AIIntelligence\DTOs\GenerateEmbeddingOutput;
use App\Application\AIIntelligence\UseCases\GenerateEmbeddingUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Events\EmbeddingGenerated;

beforeEach(function () {
    $this->embeddingGenerator = Mockery::mock(EmbeddingGeneratorInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->useCase = new GenerateEmbeddingUseCase($this->embeddingGenerator, $this->eventDispatcher);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('generates an embedding and dispatches event', function () {
    $embedding = array_fill(0, 1536, 0.1);

    $this->embeddingGenerator->shouldReceive('generate')
        ->once()
        ->with('Sample text for embedding')
        ->andReturn($embedding);

    $this->embeddingGenerator->shouldReceive('getModel')->andReturn('text-embedding-3-small');
    $this->embeddingGenerator->shouldReceive('getDimensions')->once()->andReturn(1536);

    $this->eventDispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(fn (EmbeddingGenerated $event) => $event->entityType === 'content'
            && $event->entityId === 'entity-123'
        );

    $input = new GenerateEmbeddingInput(
        organizationId: $this->orgId,
        entityType: 'content',
        entityId: 'entity-123',
        text: 'Sample text for embedding',
        userId: 'user-1',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(GenerateEmbeddingOutput::class)
        ->and($output->entityType)->toBe('content')
        ->and($output->entityId)->toBe('entity-123')
        ->and($output->embedding)->toBe($embedding)
        ->and($output->dimensions)->toBe(1536)
        ->and($output->model)->toBe('text-embedding-3-small');
});
