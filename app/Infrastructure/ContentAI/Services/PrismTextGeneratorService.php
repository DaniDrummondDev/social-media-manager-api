<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use Illuminate\Support\Facades\Http;

final class PrismTextGeneratorService implements TextGeneratorInterface
{
    public function generateTitle(
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        ?string $language = null,
    ): TextGenerationResult {
        $systemPrompt = $this->buildSystemPrompt('title', $tone, $language, $socialNetwork);
        $userPrompt = "Generate 3 title suggestions for the following topic: {$topic}";

        if ($socialNetwork !== null) {
            $userPrompt .= "\nOptimized for: {$socialNetwork}";
        }

        return $this->callAI($systemPrompt, $userPrompt, 'title');
    }

    /**
     * @param  string[]  $keywords
     */
    public function generateDescription(
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        array $keywords = [],
        ?string $language = null,
    ): TextGenerationResult {
        $systemPrompt = $this->buildSystemPrompt('description', $tone, $language, $socialNetwork);
        $userPrompt = "Generate a description/caption for the following topic: {$topic}";

        if ($socialNetwork !== null) {
            $userPrompt .= "\nOptimized for: {$socialNetwork}";
        }

        if ($keywords !== []) {
            $userPrompt .= "\nKeywords to include: ".implode(', ', $keywords);
        }

        return $this->callAI($systemPrompt, $userPrompt, 'description');
    }

    public function generateHashtags(
        string $topic,
        ?string $niche = null,
        ?string $socialNetwork = null,
    ): TextGenerationResult {
        $systemPrompt = 'You are a social media hashtag specialist. Generate relevant hashtags as a JSON array of objects with "tag" (without #) and "competition" (high/medium/low) fields.';
        $userPrompt = "Generate 10 relevant hashtags for: {$topic}";

        if ($niche !== null) {
            $userPrompt .= "\nNiche: {$niche}";
        }

        if ($socialNetwork !== null) {
            $userPrompt .= "\nPlatform: {$socialNetwork}";
        }

        return $this->callAI($systemPrompt, $userPrompt, 'hashtags');
    }

    /**
     * @param  string[]  $socialNetworks
     * @param  string[]  $keywords
     */
    public function generateFullContent(
        string $topic,
        array $socialNetworks,
        ?string $tone = null,
        array $keywords = [],
        ?string $language = null,
    ): TextGenerationResult {
        $networks = implode(', ', $socialNetworks);
        $systemPrompt = $this->buildSystemPrompt('full_content', $tone, $language);
        $systemPrompt .= "\nGenerate content adapted for each of these social networks: {$networks}. Return as a JSON object with network names as keys, each containing title, description, hashtags (array of {tag, competition}), and character_count.";

        $userPrompt = "Generate full social media content for: {$topic}";

        if ($keywords !== []) {
            $userPrompt .= "\nKeywords: ".implode(', ', $keywords);
        }

        return $this->callAI($systemPrompt, $userPrompt, 'full_content');
    }

    private function buildSystemPrompt(
        string $type,
        ?string $tone = null,
        ?string $language = null,
        ?string $socialNetwork = null,
    ): string {
        $prompt = match ($type) {
            'title' => 'You are a social media content specialist. Generate compelling titles. Return as a JSON array of objects with "title", "character_count", and "tone" fields.',
            'description' => 'You are a social media copywriter. Generate engaging descriptions/captions optimized for social media platforms. Return as a JSON object with "description", "character_count", and "max_characters" fields.',
            'full_content' => 'You are a cross-platform social media content creator.',
            default => 'You are a social media content specialist.',
        };

        if ($tone !== null) {
            $prompt .= "\nTone: {$tone}";
        }

        if ($language !== null) {
            $prompt .= "\nLanguage: {$language}";
        }

        if ($socialNetwork !== null) {
            $prompt .= "\nPlatform: {$socialNetwork}";
        }

        return $prompt;
    }

    private function callAI(string $systemPrompt, string $userPrompt, string $type): TextGenerationResult
    {
        $startTime = hrtime(true);

        /** @var string $provider */
        $provider = config('services.ai.provider', 'openai');
        /** @var string $model */
        $model = config('services.ai.model', 'gpt-4o');
        /** @var string $apiKey */
        $apiKey = config('services.ai.api_key', '');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post($this->getEndpoint($provider), [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        /** @var array<string, mixed> $output */
        $output = json_decode($content, true) ?? [];

        $tokensInput = (int) ($data['usage']['prompt_tokens'] ?? 0);
        $tokensOutput = (int) ($data['usage']['completion_tokens'] ?? 0);
        $costEstimate = $this->estimateCost($model, $tokensInput, $tokensOutput);

        return new TextGenerationResult(
            output: $output,
            tokensInput: $tokensInput,
            tokensOutput: $tokensOutput,
            model: $model,
            durationMs: $durationMs,
            costEstimate: $costEstimate,
        );
    }

    private function getEndpoint(string $provider): string
    {
        return match ($provider) {
            'openai' => 'https://api.openai.com/v1/chat/completions',
            default => (string) config('services.ai.endpoint', 'https://api.openai.com/v1/chat/completions'),
        };
    }

    private function estimateCost(string $model, int $tokensInput, int $tokensOutput): float
    {
        $rates = match ($model) {
            'gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            default => ['input' => 0.001, 'output' => 0.002],
        };

        return ($tokensInput / 1000 * $rates['input']) + ($tokensOutput / 1000 * $rates['output']);
    }
}
