<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\ContentAI\Contracts\RAGContextProviderInterface;
use App\Application\ContentAI\DTOs\RAGContextResult;

final class StubRAGContextProvider implements RAGContextProviderInterface
{
    public function retrieve(
        string $organizationId,
        string $topic,
        ?string $provider = null,
        int $limit = 5,
    ): RAGContextResult {
        return new RAGContextResult(
            contentIds: [],
            formattedExamples: '',
            tokenCount: 0,
        );
    }
}
