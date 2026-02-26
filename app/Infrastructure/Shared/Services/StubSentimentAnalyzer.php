<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Services;

use App\Application\Shared\Contracts\SentimentAnalyzerInterface;
use App\Application\Shared\DTOs\SentimentResult;

final class StubSentimentAnalyzer implements SentimentAnalyzerInterface
{
    public function analyze(string $text): SentimentResult
    {
        return new SentimentResult(
            sentiment: 'neutral',
            score: 0.5,
        );
    }
}
