<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

interface SimilaritySearchInterface
{
    /**
     * @param  array<float>  $embedding
     * @return array<array{content_id: string, similarity: float}>
     */
    public function findSimilar(
        array $embedding,
        string $organizationId,
        ?string $provider = null,
        int $limit = 5,
    ): array;

    /**
     * @param  array<array<float>>  $embeddings
     * @return array<float>
     */
    public function calculateCentroid(array $embeddings): array;
}
