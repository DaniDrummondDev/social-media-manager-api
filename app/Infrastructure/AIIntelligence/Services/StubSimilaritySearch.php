<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;

final class StubSimilaritySearch implements SimilaritySearchInterface
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
    ): array {
        return [];
    }

    /**
     * @param  array<array<float>>  $embeddings
     * @return array<float>
     */
    public function calculateCentroid(array $embeddings): array
    {
        if ($embeddings === []) {
            return [];
        }

        $dimensions = count($embeddings[0]);
        $count = count($embeddings);
        $centroid = array_fill(0, $dimensions, 0.0);

        foreach ($embeddings as $embedding) {
            foreach ($embedding as $i => $value) {
                $centroid[$i] += $value;
            }
        }

        return array_map(fn (float $sum) => $sum / $count, $centroid);
    }
}
