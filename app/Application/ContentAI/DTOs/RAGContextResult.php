<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class RAGContextResult
{
    /**
     * @param  array<string>  $contentIds
     */
    public function __construct(
        public array $contentIds,
        public string $formattedExamples,
        public int $tokenCount,
    ) {}

    public static function empty(): self
    {
        return new self(
            contentIds: [],
            formattedExamples: '',
            tokenCount: 0,
        );
    }

    public function isEmpty(): bool
    {
        return $this->contentIds === [];
    }
}
