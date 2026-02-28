<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\Shared\Contracts\SentimentAnalyzerInterface;
use App\Application\Shared\DTOs\SentimentResult;
use EchoLabs\Prism\Facades\Prism;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\Schema\NumberSchema;
use Illuminate\Support\Facades\Log;

final class PrismSentimentAnalyzer implements SentimentAnalyzerInterface
{
    private const PROVIDER = 'openai';

    private const MODEL = 'gpt-4o-mini';

    public function analyze(string $text): SentimentResult
    {
        if (trim($text) === '') {
            return new SentimentResult(
                sentiment: 'neutral',
                score: 0.5,
            );
        }

        try {
            $response = Prism::structured()
                ->using(self::PROVIDER, self::MODEL)
                ->withSchema($this->buildSchema())
                ->withSystemPrompt($this->getSystemPrompt())
                ->withPrompt("Analyze the sentiment of this text:\n\n{$text}")
                ->generate();

            /** @var array{sentiment: string, score: float, confidence: float} $result */
            $result = $response->structured;

            $this->logUsage($response);

            return new SentimentResult(
                sentiment: $this->normalizeSentiment($result['sentiment'] ?? 'neutral'),
                score: $this->normalizeScore($result['score'] ?? 0.5),
            );
        } catch (\Throwable $e) {
            Log::warning('PrismSentimentAnalyzer: Failed to analyze sentiment', [
                'error' => $e->getMessage(),
                'text_length' => mb_strlen($text),
            ]);

            // Graceful fallback to neutral sentiment
            return new SentimentResult(
                sentiment: 'neutral',
                score: 0.5,
            );
        }
    }

    private function buildSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'sentiment_analysis',
            description: 'Sentiment analysis result',
            properties: [
                new StringSchema(
                    name: 'sentiment',
                    description: 'The detected sentiment: positive, negative, neutral, or mixed',
                ),
                new NumberSchema(
                    name: 'score',
                    description: 'Sentiment score from 0.0 (most negative) to 1.0 (most positive)',
                ),
                new NumberSchema(
                    name: 'confidence',
                    description: 'Confidence level from 0.0 to 1.0',
                ),
            ],
            requiredFields: ['sentiment', 'score', 'confidence'],
        );
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a sentiment analysis expert. Analyze the given text and determine its sentiment.

Classification rules:
- "positive": Text expresses happiness, satisfaction, enthusiasm, or approval
- "negative": Text expresses sadness, anger, frustration, or disapproval
- "neutral": Text is factual, informational, or lacks emotional content
- "mixed": Text contains both positive and negative sentiments

Score guidelines:
- 0.0-0.25: Strongly negative
- 0.25-0.45: Somewhat negative
- 0.45-0.55: Neutral
- 0.55-0.75: Somewhat positive
- 0.75-1.0: Strongly positive

Consider context, tone, and implicit sentiment in your analysis.
PROMPT;
    }

    private function normalizeSentiment(string $sentiment): string
    {
        $sentiment = strtolower(trim($sentiment));

        return match ($sentiment) {
            'positive', 'negative', 'neutral', 'mixed' => $sentiment,
            default => 'neutral',
        };
    }

    private function normalizeScore(float $score): float
    {
        return max(0.0, min(1.0, $score));
    }

    private function logUsage(object $response): void
    {
        $usage = $response->usage ?? null;

        Log::debug('PrismSentimentAnalyzer: Analyzed sentiment', [
            'model' => self::MODEL,
            'tokens_input' => $usage->promptTokens ?? 0,
            'tokens_output' => $usage->completionTokens ?? 0,
        ]);
    }
}
