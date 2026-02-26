<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;
use App\Application\AIIntelligence\DTOs\RetrieveSimilarContentInput;
use App\Application\ContentAI\DTOs\RAGContextResult;

final class RetrieveSimilarContentUseCase
{
    public function __construct(
        private readonly EmbeddingGeneratorInterface $embeddingGenerator,
        private readonly SimilaritySearchInterface $similaritySearch,
    ) {}

    /**
     * Retrieve similar high-performing content via pgvector cosine similarity.
     *
     * Returns empty result when insufficient published content exists (RN-ALL-17).
     */
    public function execute(RetrieveSimilarContentInput $input): RAGContextResult
    {
        $embedding = $this->embeddingGenerator->generate($input->topic);

        $similar = $this->similaritySearch->findSimilar(
            embedding: $embedding,
            organizationId: $input->organizationId,
            provider: $input->provider,
            limit: $input->limit,
        );

        if ($similar === []) {
            return new RAGContextResult(
                contentIds: [],
                formattedExamples: '',
                tokenCount: 0,
            );
        }

        $contentIds = array_map(fn (array $s) => $s['content_id'], $similar);

        $formattedExamples = $this->formatExamples($similar);
        $tokenCount = (int) ceil(mb_strlen($formattedExamples) / 4);

        return new RAGContextResult(
            contentIds: $contentIds,
            formattedExamples: $formattedExamples,
            tokenCount: $tokenCount,
        );
    }

    /**
     * @param  array<array{content_id: string, similarity: float}>  $similar
     */
    private function formatExamples(array $similar): string
    {
        $lines = [];
        foreach ($similar as $index => $item) {
            $lines[] = sprintf(
                '--- Example %d (similarity: %.2f) ---',
                $index + 1,
                $item['similarity'],
            );
            $lines[] = sprintf('Content ID: %s', $item['content_id']);
        }

        return implode("\n", $lines);
    }
}
