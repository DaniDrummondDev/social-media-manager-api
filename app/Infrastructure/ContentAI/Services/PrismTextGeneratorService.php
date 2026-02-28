<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\AIIntelligence\Contracts\AudienceInsightAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\AudienceInsightAnalysisResult;
use App\Application\AIIntelligence\DTOs\StyleAnalysisResult;
use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\Contracts\RAGContextProviderInterface;
use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\RAGContextResult;
use App\Application\ContentAI\DTOs\ResolvedPromptResult;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PrismTextGeneratorService implements TextGeneratorInterface
{
    public function __construct(
        private readonly ?RAGContextProviderInterface $ragContextProvider = null,
        private readonly ?PromptTemplateResolverInterface $promptTemplateResolver = null,
        private readonly ?StyleProfileAnalyzerInterface $styleProfileAnalyzer = null,
        private readonly ?AudienceInsightAnalyzerInterface $audienceInsightAnalyzer = null,
    ) {}

    public function generateTitle(
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        ?string $language = null,
        ?string $organizationId = null,
    ): TextGenerationResult {
        $generationType = 'title';

        if ($organizationId !== null) {
            return $this->generateWithEnrichment(
                $organizationId,
                $generationType,
                $topic,
                $socialNetwork,
                $tone,
                $language,
            );
        }

        $systemPrompt = $this->buildSystemPrompt($generationType, $tone, $language, $socialNetwork);
        $userPrompt = "Generate 3 title suggestions for the following topic: {$topic}";

        if ($socialNetwork !== null) {
            $userPrompt .= "\nOptimized for: {$socialNetwork}";
        }

        return $this->callAI($systemPrompt, $userPrompt, $generationType);
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
        ?string $organizationId = null,
    ): TextGenerationResult {
        $generationType = 'description';

        if ($organizationId !== null) {
            return $this->generateWithEnrichment(
                $organizationId,
                $generationType,
                $topic,
                $socialNetwork,
                $tone,
                $language,
                $keywords,
            );
        }

        $systemPrompt = $this->buildSystemPrompt($generationType, $tone, $language, $socialNetwork);
        $userPrompt = "Generate a description/caption for the following topic: {$topic}";

        if ($socialNetwork !== null) {
            $userPrompt .= "\nOptimized for: {$socialNetwork}";
        }

        if ($keywords !== []) {
            $userPrompt .= "\nKeywords to include: ".implode(', ', $keywords);
        }

        return $this->callAI($systemPrompt, $userPrompt, $generationType);
    }

    public function generateHashtags(
        string $topic,
        ?string $niche = null,
        ?string $socialNetwork = null,
        ?string $organizationId = null,
    ): TextGenerationResult {
        $generationType = 'hashtags';

        if ($organizationId !== null) {
            return $this->generateWithEnrichment(
                $organizationId,
                $generationType,
                $topic,
                $socialNetwork,
                niche: $niche,
            );
        }

        $systemPrompt = 'You are a social media hashtag specialist. Generate relevant hashtags as a JSON array of objects with "tag" (without #) and "competition" (high/medium/low) fields.';
        $userPrompt = "Generate 10 relevant hashtags for: {$topic}";

        if ($niche !== null) {
            $userPrompt .= "\nNiche: {$niche}";
        }

        if ($socialNetwork !== null) {
            $userPrompt .= "\nPlatform: {$socialNetwork}";
        }

        return $this->callAI($systemPrompt, $userPrompt, $generationType);
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
        ?string $organizationId = null,
    ): TextGenerationResult {
        $generationType = 'full_content';
        $networks = implode(', ', $socialNetworks);

        if ($organizationId !== null) {
            return $this->generateWithEnrichment(
                $organizationId,
                $generationType,
                $topic,
                $networks,
                $tone,
                $language,
                $keywords,
            );
        }

        $systemPrompt = $this->buildSystemPrompt($generationType, $tone, $language);
        $systemPrompt .= "\nGenerate content adapted for each of these social networks: {$networks}. Return as a JSON object with network names as keys, each containing title, description, hashtags (array of {tag, competition}), and character_count.";

        $userPrompt = "Generate full social media content for: {$topic}";

        if ($keywords !== []) {
            $userPrompt .= "\nKeywords: ".implode(', ', $keywords);
        }

        return $this->callAI($systemPrompt, $userPrompt, $generationType);
    }

    /**
     * @param  string[]  $targetNetworks
     */
    public function adaptContent(
        string $contentId,
        string $organizationId,
        string $sourceNetwork,
        array $targetNetworks,
        bool $preserveTone,
    ): TextGenerationResult {
        $networks = implode(', ', $targetNetworks);
        $systemPrompt = "You are a cross-network content adaptation specialist. Adapt content from {$sourceNetwork} to: {$networks}. Respect each platform's character limits, hashtag conventions, and content style.";

        if ($preserveTone) {
            $systemPrompt .= "\nPreserve the original tone and voice.";
        }

        $userPrompt = "Adapt content ID {$contentId} from {$sourceNetwork} to the target networks. Return as a JSON object with each target network as a key, containing title, description, hashtags (array), character_count (object with title and description counts), and changes_summary.";

        return $this->callAI($systemPrompt, $userPrompt, 'cross_network_adaptation');
    }

    /**
     * @param  string[]  $keywords
     */
    private function generateWithEnrichment(
        string $organizationId,
        string $generationType,
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        ?string $language = null,
        array $keywords = [],
        ?string $niche = null,
    ): TextGenerationResult {
        // 1. Resolve template
        $template = $this->safeResolveTemplate($organizationId, $generationType);

        // 2. Fetch RAG examples (graceful fallback)
        $ragContext = $this->safeGetRagContext($organizationId, $topic, $socialNetwork);

        // 3. Get style profile (graceful fallback)
        $styleProfile = $this->safeGetStyleProfile($organizationId, $generationType);

        // 4. Get audience insights (graceful fallback)
        $audienceInsights = $this->safeGetAudienceInsights($organizationId);

        // 5. Build enriched prompt
        $enrichedPrompt = $this->buildEnrichedPrompt(
            $template,
            $ragContext,
            $styleProfile,
            $audienceInsights,
            $generationType,
            $topic,
            $socialNetwork,
            $tone,
            $language,
            $keywords,
            $niche,
        );

        // 6. Call AI
        return $this->callAI($enrichedPrompt['system'], $enrichedPrompt['user'], $generationType);
    }

    private function safeResolveTemplate(string $organizationId, string $generationType): ResolvedPromptResult
    {
        if ($this->promptTemplateResolver === null) {
            return $this->getDefaultTemplate($generationType);
        }

        try {
            return $this->promptTemplateResolver->resolve($organizationId, $generationType);
        } catch (\Throwable $e) {
            Log::warning('PrismTextGeneratorService: Template resolution failed, using default', [
                'error' => $e->getMessage(),
                'organization_id' => $organizationId,
                'generation_type' => $generationType,
            ]);

            return $this->getDefaultTemplate($generationType);
        }
    }

    private function safeGetRagContext(
        string $organizationId,
        string $topic,
        ?string $provider,
    ): RAGContextResult {
        if ($this->ragContextProvider === null) {
            return RAGContextResult::empty();
        }

        try {
            return $this->ragContextProvider->retrieve($organizationId, $topic, $provider, 5);
        } catch (\Throwable $e) {
            Log::warning('PrismTextGeneratorService: RAG context failed, continuing without', [
                'error' => $e->getMessage(),
                'organization_id' => $organizationId,
            ]);

            return RAGContextResult::empty();
        }
    }

    private function safeGetStyleProfile(string $organizationId, string $generationType): StyleAnalysisResult
    {
        if ($this->styleProfileAnalyzer === null) {
            return StyleAnalysisResult::empty();
        }

        try {
            return $this->styleProfileAnalyzer->analyzeEditPatterns($organizationId, $generationType);
        } catch (\Throwable $e) {
            Log::warning('PrismTextGeneratorService: Style profile failed, continuing without', [
                'error' => $e->getMessage(),
                'organization_id' => $organizationId,
            ]);

            return StyleAnalysisResult::empty();
        }
    }

    private function safeGetAudienceInsights(string $organizationId): AudienceInsightAnalysisResult
    {
        if ($this->audienceInsightAnalyzer === null) {
            return AudienceInsightAnalysisResult::empty();
        }

        try {
            return $this->audienceInsightAnalyzer->analyze([], InsightType::AudiencePreferences, $organizationId);
        } catch (\Throwable $e) {
            Log::warning('PrismTextGeneratorService: Audience insights failed, continuing without', [
                'error' => $e->getMessage(),
                'organization_id' => $organizationId,
            ]);

            return AudienceInsightAnalysisResult::empty();
        }
    }

    /**
     * @param  string[]  $keywords
     * @return array{system: string, user: string}
     */
    private function buildEnrichedPrompt(
        ResolvedPromptResult $template,
        RAGContextResult $ragContext,
        StyleAnalysisResult $styleProfile,
        AudienceInsightAnalysisResult $audienceInsights,
        string $generationType,
        string $topic,
        ?string $socialNetwork,
        ?string $tone,
        ?string $language,
        array $keywords,
        ?string $niche,
    ): array {
        // Build system prompt with enrichments
        $systemPrompt = $template->systemPrompt;

        // Add style preferences
        if (! $styleProfile->isEmpty()) {
            $styleContext = $this->formatStyleContext($styleProfile);
            $systemPrompt .= "\n\n## Style Guidelines (learned from user edits)\n{$styleContext}";
        }

        // Add audience insights
        if (! $audienceInsights->isEmpty()) {
            $audienceContext = $this->formatAudienceContext($audienceInsights);
            $systemPrompt .= "\n\n## Audience Preferences\n{$audienceContext}";
        }

        // Build user prompt from template
        $userPrompt = $this->interpolateTemplate(
            $template->userPromptTemplate,
            $generationType,
            $topic,
            $socialNetwork,
            $tone,
            $language,
            $keywords,
            $niche,
        );

        // Add RAG examples
        if (! $ragContext->isEmpty()) {
            $userPrompt .= "\n\n## High-Performing Examples from Your Content History\n{$ragContext->formattedExamples}";
        }

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    private function formatStyleContext(StyleAnalysisResult $styleProfile): string
    {
        $context = [];

        if (isset($styleProfile->tonePreferences['preferred'])) {
            $context[] = "- Preferred tone: {$styleProfile->tonePreferences['preferred']}";
        }

        if (isset($styleProfile->lengthPreferences['avg_preferred_length'])) {
            $context[] = "- Preferred length: ~{$styleProfile->lengthPreferences['avg_preferred_length']} characters";
        }

        if (isset($styleProfile->structurePreferences['uses_emojis']) && $styleProfile->structurePreferences['uses_emojis']) {
            $context[] = '- Include emojis in the content';
        }

        if (isset($styleProfile->structurePreferences['uses_questions']) && $styleProfile->structurePreferences['uses_questions']) {
            $context[] = '- Use rhetorical questions to engage the audience';
        }

        if ($styleProfile->styleSummary !== null) {
            $context[] = "\nStyle summary: {$styleProfile->styleSummary}";
        }

        return implode("\n", $context);
    }

    private function formatAudienceContext(AudienceInsightAnalysisResult $audienceInsights): string
    {
        $context = [];

        if (isset($audienceInsights->insightData['preferences'])) {
            foreach ($audienceInsights->insightData['preferences'] as $pref) {
                if (isset($pref['category'], $pref['value'])) {
                    $context[] = "- {$pref['category']}: {$pref['value']}";
                }
            }
        }

        if (isset($audienceInsights->insightData['topics'])) {
            $topics = array_column($audienceInsights->insightData['topics'], 'name');
            if ($topics !== []) {
                $context[] = '- Preferred topics: '.implode(', ', array_slice($topics, 0, 5));
            }
        }

        return $context !== [] ? implode("\n", $context) : 'No specific audience preferences available.';
    }

    /**
     * @param  string[]  $keywords
     */
    private function interpolateTemplate(
        string $template,
        string $generationType,
        string $topic,
        ?string $socialNetwork,
        ?string $tone,
        ?string $language,
        array $keywords,
        ?string $niche,
    ): string {
        $replacements = [
            '{generation_type}' => $generationType,
            '{topic}' => $topic,
            '{social_network}' => $socialNetwork ?? 'general',
            '{social_networks}' => $socialNetwork ?? 'general',
            '{tone}' => $tone ?? 'professional',
            '{language}' => $language ?? 'en-US',
            '{keywords}' => implode(', ', $keywords),
            '{niche}' => $niche ?? 'general',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function getDefaultTemplate(string $generationType): ResolvedPromptResult
    {
        $systemPrompts = [
            'title' => 'You are a social media content specialist. Generate compelling titles. Return as a JSON array of objects with "title", "character_count", and "tone" fields.',
            'description' => 'You are a social media copywriter. Generate engaging descriptions/captions optimized for social media platforms. Return as a JSON object with "description", "character_count", and "max_characters" fields.',
            'hashtags' => 'You are a social media hashtag specialist. Generate relevant hashtags as a JSON array of objects with "tag" (without #) and "competition" (high/medium/low) fields.',
            'full_content' => 'You are a cross-platform social media content creator. Generate comprehensive content adapted for different platforms.',
        ];

        return new ResolvedPromptResult(
            templateId: '00000000-0000-0000-0000-000000000001',
            experimentId: null,
            systemPrompt: $systemPrompts[$generationType] ?? "You are a social media content expert. Generate {$generationType} content.",
            userPromptTemplate: 'Generate a {generation_type} about {topic} for {social_network}.',
            variables: ['generation_type', 'topic', 'social_network'],
            variantSelected: null,
        );
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
