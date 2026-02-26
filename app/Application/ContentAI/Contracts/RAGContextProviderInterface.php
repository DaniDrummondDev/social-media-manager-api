<?php

declare(strict_types=1);

namespace App\Application\ContentAI\Contracts;

use App\Application\ContentAI\DTOs\RAGContextResult;

interface RAGContextProviderInterface
{
    /**
     * Retrieve similar high-performing content via pgvector cosine similarity.
     *
     * Filters: published contents only, engagement_rate > org median.
     * Returns empty result (not error) when < 5 published contents with embeddings.
     */
    public function retrieve(
        string $organizationId,
        string $topic,
        ?string $provider = null,
        int $limit = 5,
    ): RAGContextResult;
}
