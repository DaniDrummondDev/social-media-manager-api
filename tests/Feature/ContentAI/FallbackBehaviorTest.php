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
                ['message' => ['content' => '{"title": "Test"}']],
            ],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
        ]),
    ]);

    $this->ragProvider = Mockery::mock(RAGContextProviderInterface::class);
    $this->templateResolver = Mockery::mock(PromptTemplateResolverInterface::class);
    $this->styleAnalyzer = Mockery::mock(StyleProfileAnalyzerInterface::class);
    $this->audienceAnalyzer = Mockery::mock(AudienceInsightAnalyzerInterface::class);
});

it('continues generation when RAG provider fails', function () {
    $this->ragProvider->shouldReceive('retrieve')
        ->andThrow(new \Exception('DB error'));

    $this->templateResolver->shouldReceive('resolve')
        ->andReturn(new ResolvedPromptResult(
            templateId: 'test-id',
            experimentId: null,
            systemPrompt: 'Test system prompt.',
            userPromptTemplate: 'Generate for {topic}.',
            variables: ['topic'],
            variantSelected: null,
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

    // This should not throw, even though RAG provider fails
    expect(fn () => $service->generateTitle('topic', 'instagram', 'casual', 'en', 'org-id'))
        ->not->toThrow(\Exception::class);
});

it('continues generation when template resolver fails', function () {
    $this->templateResolver->shouldReceive('resolve')
        ->andThrow(new \Exception('Template error'));

    $this->ragProvider->shouldReceive('retrieve')
        ->andReturn(RAGContextResult::empty());

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

    // This should not throw, even though template resolver fails
    expect(fn () => $service->generateTitle('topic', 'instagram', 'casual', 'en', 'org-id'))
        ->not->toThrow(\Exception::class);
});

it('continues generation when style analyzer fails', function () {
    $this->styleAnalyzer->shouldReceive('analyzeEditPatterns')
        ->andThrow(new \Exception('Style error'));

    $this->templateResolver->shouldReceive('resolve')
        ->andReturn(new ResolvedPromptResult(
            templateId: 'test-id',
            experimentId: null,
            systemPrompt: 'Test.',
            userPromptTemplate: 'Generate for {topic}.',
            variables: ['topic'],
            variantSelected: null,
        ));

    $this->ragProvider->shouldReceive('retrieve')
        ->andReturn(RAGContextResult::empty());

    $this->audienceAnalyzer->shouldReceive('analyze')
        ->andReturn(AudienceInsightAnalysisResult::empty());

    $service = new PrismTextGeneratorService(
        ragContextProvider: $this->ragProvider,
        promptTemplateResolver: $this->templateResolver,
        styleProfileAnalyzer: $this->styleAnalyzer,
        audienceInsightAnalyzer: $this->audienceAnalyzer,
    );

    // This should not throw, even though style analyzer fails
    expect(fn () => $service->generateTitle('topic', 'instagram', 'casual', 'en', 'org-id'))
        ->not->toThrow(\Exception::class);
});

it('continues generation when audience analyzer fails', function () {
    $this->audienceAnalyzer->shouldReceive('analyze')
        ->andThrow(new \Exception('Audience error'));

    $this->templateResolver->shouldReceive('resolve')
        ->andReturn(new ResolvedPromptResult(
            templateId: 'test-id',
            experimentId: null,
            systemPrompt: 'Test.',
            userPromptTemplate: 'Generate for {topic}.',
            variables: ['topic'],
            variantSelected: null,
        ));

    $this->ragProvider->shouldReceive('retrieve')
        ->andReturn(RAGContextResult::empty());

    $this->styleAnalyzer->shouldReceive('analyzeEditPatterns')
        ->andReturn(StyleAnalysisResult::empty());

    $service = new PrismTextGeneratorService(
        ragContextProvider: $this->ragProvider,
        promptTemplateResolver: $this->templateResolver,
        styleProfileAnalyzer: $this->styleAnalyzer,
        audienceInsightAnalyzer: $this->audienceAnalyzer,
    );

    // This should not throw, even though audience analyzer fails
    expect(fn () => $service->generateTitle('topic', 'instagram', 'casual', 'en', 'org-id'))
        ->not->toThrow(\Exception::class);
});

it('works without any enrichment providers', function () {
    $service = new PrismTextGeneratorService(
        ragContextProvider: null,
        promptTemplateResolver: null,
        styleProfileAnalyzer: null,
        audienceInsightAnalyzer: null,
    );

    // This should not throw
    expect(fn () => $service->generateTitle('topic', 'instagram', 'casual', 'en', null))
        ->not->toThrow(\Exception::class);
});
