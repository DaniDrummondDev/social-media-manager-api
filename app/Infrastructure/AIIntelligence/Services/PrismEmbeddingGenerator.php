<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use EchoLabs\Prism\Facades\Prism;
use Illuminate\Support\Facades\Log;

final class PrismEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    private const MODEL = 'text-embedding-3-small';

    private const DIMENSIONS = 1536;

    private const PROVIDER = 'openai';

    /**
     * @return array<float>
     */
    public function generate(string $text): array
    {
        try {
            $response = Prism::embeddings()
                ->using(self::PROVIDER, self::MODEL)
                ->fromInput($text)
                ->generate();

            /** @var array<float> $embedding */
            $embedding = $response->embeddings[0] ?? [];

            $this->logUsage(1, $response->usage->tokens ?? 0);

            return $embedding;
        } catch (\Throwable $e) {
            Log::error('PrismEmbeddingGenerator: Failed to generate embedding', [
                'error' => $e->getMessage(),
                'text_length' => mb_strlen($text),
            ]);

            // Return zero vector on failure (graceful degradation)
            return array_fill(0, self::DIMENSIONS, 0.0);
        }
    }

    /**
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function generateBatch(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        try {
            $response = Prism::embeddings()
                ->using(self::PROVIDER, self::MODEL)
                ->fromInput($texts)
                ->generate();

            /** @var array<array<float>> $embeddings */
            $embeddings = $response->embeddings ?? [];

            $this->logUsage(count($texts), $response->usage->tokens ?? 0);

            return $embeddings;
        } catch (\Throwable $e) {
            Log::error('PrismEmbeddingGenerator: Failed to generate batch embeddings', [
                'error' => $e->getMessage(),
                'batch_size' => count($texts),
            ]);

            // Return zero vectors on failure
            return array_map(
                fn () => array_fill(0, self::DIMENSIONS, 0.0),
                $texts,
            );
        }
    }

    public function getModel(): string
    {
        return self::MODEL;
    }

    public function getDimensions(): int
    {
        return self::DIMENSIONS;
    }

    private function logUsage(int $inputCount, int $tokensUsed): void
    {
        Log::debug('PrismEmbeddingGenerator: Generated embeddings', [
            'model' => self::MODEL,
            'input_count' => $inputCount,
            'tokens_used' => $tokensUsed,
        ]);
    }
}
