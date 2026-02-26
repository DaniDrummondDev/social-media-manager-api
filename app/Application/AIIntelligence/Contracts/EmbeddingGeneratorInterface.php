<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

interface EmbeddingGeneratorInterface
{
    /**
     * @return array<float>
     */
    public function generate(string $text): array;

    /**
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function generateBatch(array $texts): array;

    public function getModel(): string;

    public function getDimensions(): int;
}
