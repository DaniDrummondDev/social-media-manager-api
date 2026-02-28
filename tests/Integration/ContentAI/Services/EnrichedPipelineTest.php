<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\AudienceInsightAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\AudienceInsightAnalysisResult;
use App\Application\AIIntelligence\DTOs\StyleAnalysisResult;
use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\Contracts\RAGContextProviderInterface;
use App\Application\ContentAI\DTOs\RAGContextResult;
use App\Application\ContentAI\DTOs\ResolvedPromptResult;
use App\Infrastructure\ContentAI\Services\PrismTextGeneratorService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"title": "Test Title", "character_count": 10}']],
            ],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
        ]),
    ]);

    $this->ragProvider = Mockery::mock(RAGContextProviderInterface::class);
    $this->templateResolver = Mockery::mock(PromptTemplateResolverInterface::class);
    $this->styleAnalyzer = Mockery::mock(StyleProfileAnalyzerInterface::class);
    $this->audienceAnalyzer = Mockery::mock(AudienceInsightAnalyzerInterface::class);
});

it('enriches prompt with RAG context when available', function () {
    $this->ragProvider->shouldReceive('retrieve')
        ->once()
        ->andReturn(new RAGContextResult(
            contentIds: ['content-1', 'content-2'],
            formattedExamples: "Example 1: Great title\nExample 2: Another title",
            tokenCount: 200,
        ));

    $this->templateResolver->shouldReceive('resolve')
        ->once()
        ->andReturn(new ResolvedPromptResult(
            templateId: 'test-id',
            experimentId: null,
            systemPrompt: 'You are a social media expert.',
            userPromptTemplate: 'Generate a title for: {topic}',
            variables: ['topic'],
            variantSelected: null,
        ));

    $this->styleAnalyzer->shouldReceive('analyzeEditPatterns')
        ->once()
        ->andReturn(StyleAnalysisResult::empty());

    $this->audienceAnalyzer->shouldReceive('analyze')
        ->once()
        ->andReturn(AudienceInsightAnalysisResult::empty());

    $service = new PrismTextGeneratorService(
        ragContextProvider: $this->ragProvider,
        promptTemplateResolver: $this->templateResolver,
        styleProfileAnalyzer: $this->styleAnalyzer,
        audienceInsightAnalyzer: $this->audienceAnalyzer,
    );

    $result = $service->generateTitle('productivity', 'instagram', 'casual', 'en-US', 'org-123');

    expect($result->output)->toHaveKey('title')
        ->and($result->tokensInput)->toBeGreaterThan(0)
        ->and($result->model)->toBe('gpt-4o');
});

it('enriches prompt with style profile when available', function () {
    $this->ragProvider->shouldReceive('retrieve')
        ->andReturn(RAGContextResult::empty());

    $this->templateResolver->shouldReceive('resolve')
        ->andReturn(new ResolvedPromptResult(
            templateId: 'test-id',
            experimentId: null,
            systemPrompt: 'You are a social media expert.',
            userPromptTemplate: 'Generate a title for: {topic}',
            variables: ['topic'],
            variantSelected: null,
        ));

    $this->styleAnalyzer->shouldReceive('analyzeEditPatterns')
        ->once()
        ->andReturn(new StyleAnalysisResult(
            tonePreferences: ['preferred' => 'casual'],
            lengthPreferences: ['avg_preferred_length' => 50],
            vocabularyPreferences: [],
            structurePreferences: ['uses_emojis' => true],
            hashtagPreferences: [],
            styleSummary: 'Casual with emojis.',
            sampleSize: 30,
        ));

    $this->audienceAnalyzer->shouldReceive('analyze')
        ->andReturn(AudienceInsightAnalysisResult::empty());

    $service = new PrismTextGeneratorService(
        ragContextProvider: $this->ragProvider,
        promptTemplateResolver: $this->templateResolver,
        styleProfileAnalyzer: $this->styleAnalyzer,
        audienceInsightAnalyzer: $this->audienceAnalyzer,
    );

    $result = $service->generateTitle('productivity', 'instagram', null, 'en-US', 'org-123');

    expect($result->output)->toHaveKey('title');
});

it('uses non-enriched pipeline when organizationId is null', function () {
    // No mocks should be called when organizationId is null

    $service = new PrismTextGeneratorService(
        ragContextProvider: $this->ragProvider,
        promptTemplateResolver: $this->templateResolver,
        styleProfileAnalyzer: $this->styleAnalyzer,
        audienceInsightAnalyzer: $this->audienceAnalyzer,
    );

    $result = $service->generateTitle('productivity', 'instagram', 'casual', 'en-US', null);

    expect($result->output)->toHaveKey('title');
});

it('tracks enrichment via experiment id when A/B testing', function () {
    $this->ragProvider->shouldReceive('retrieve')
        ->andReturn(RAGContextResult::empty());

    $this->templateResolver->shouldReceive('resolve')
        ->andReturn(new ResolvedPromptResult(
            templateId: 'variant-a-id',
            experimentId: 'experiment-123',
            systemPrompt: 'Variant A system prompt.',
            userPromptTemplate: 'Variant A: {topic}',
            variables: ['topic'],
            variantSelected: 'A',
        ));

    $this->styleAnalyzer->shouldReceive('analyzeEditPatterns')
        ->andReturn(StyleAnalysisResult::empty());

    $this->audienceAnalyzer->shouldReceive('analyze')
        ->andReturn(AudienceInsightAnalysisResult::empty());

    $service = new PrismTextGeneratorService(
        ragContextProvider: $this->ragProvider,
        promptTemplateResolver: $this->templateResolver,
        styleProfileAnalyzer: $this->styleAnalyzer,
        audienceInsightAnalyzer: $this->audienceAnalyzer,
    );

    $result = $service->generateTitle('productivity', 'instagram', null, 'en-US', 'org-123');

    expect($result->output)->toHaveKey('title');
});
