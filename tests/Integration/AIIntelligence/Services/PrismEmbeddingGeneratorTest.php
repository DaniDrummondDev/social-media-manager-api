<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Infrastructure\AIIntelligence\Services\PrismEmbeddingGenerator;

beforeEach(function () {
    $this->generator = new PrismEmbeddingGenerator();
});

it('returns correct model name', function () {
    expect($this->generator->getModel())->toBe('text-embedding-3-small');
});

it('returns correct dimensions', function () {
    expect($this->generator->getDimensions())->toBe(1536);
});

it('resolves from container', function () {
    $generator = app(EmbeddingGeneratorInterface::class);

    expect($generator)->toBeInstanceOf(PrismEmbeddingGenerator::class);
});

it('returns zero vector on empty batch', function () {
    $result = $this->generator->generateBatch([]);

    expect($result)->toBeEmpty();
});
