<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;

final class StubEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    /**
     * @return array<float>
     */
    public function generate(string $text): array
    {
        return array_fill(0, 1536, 0.1);
    }

    /**
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function generateBatch(array $texts): array
    {
        return array_map(fn () => array_fill(0, 1536, 0.1), $texts);
    }

    public function getModel(): string
    {
        return 'text-embedding-3-small-stub';
    }

    public function getDimensions(): int
    {
        return 1536;
    }
}
