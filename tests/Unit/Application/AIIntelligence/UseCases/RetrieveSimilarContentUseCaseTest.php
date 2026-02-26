<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;
use App\Application\AIIntelligence\DTOs\RetrieveSimilarContentInput;
use App\Application\AIIntelligence\UseCases\RetrieveSimilarContentUseCase;
use App\Application\ContentAI\DTOs\RAGContextResult;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->embeddingGenerator = Mockery::mock(EmbeddingGeneratorInterface::class);
    $this->similaritySearch = Mockery::mock(SimilaritySearchInterface::class);

    $this->useCase = new RetrieveSimilarContentUseCase(
        $this->embeddingGenerator,
        $this->similaritySearch,
    );
});

it('retrieves similar content and returns formatted result', function () {
    $embedding = [0.1, 0.2, 0.3];

    $this->embeddingGenerator->shouldReceive('generate')->once()->andReturn($embedding);
    $this->similaritySearch->shouldReceive('findSimilar')->once()->andReturn([
        ['content_id' => 'id-1', 'similarity' => 0.95],
        ['content_id' => 'id-2', 'similarity' => 0.88],
    ]);

    $output = $this->useCase->execute(new RetrieveSimilarContentInput(
        organizationId: (string) Uuid::generate(),
        topic: 'social media marketing tips',
        provider: 'instagram',
        limit: 5,
    ));

    expect($output)->toBeInstanceOf(RAGContextResult::class)
        ->and($output->contentIds)->toBe(['id-1', 'id-2'])
        ->and($output->formattedExamples)->toContain('Example 1')
        ->and($output->formattedExamples)->toContain('0.95')
        ->and($output->tokenCount)->toBeGreaterThan(0);
});

it('returns empty result when no similar content exists', function () {
    $this->embeddingGenerator->shouldReceive('generate')->once()->andReturn([0.1, 0.2]);
    $this->similaritySearch->shouldReceive('findSimilar')->once()->andReturn([]);

    $output = $this->useCase->execute(new RetrieveSimilarContentInput(
        organizationId: (string) Uuid::generate(),
        topic: 'obscure topic with no matches',
    ));

    expect($output->contentIds)->toBeEmpty()
        ->and($output->formattedExamples)->toBe('')
        ->and($output->tokenCount)->toBe(0);
});
