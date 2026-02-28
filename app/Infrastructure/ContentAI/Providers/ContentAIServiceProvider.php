<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Providers;

use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\Contracts\RAGContextProviderInterface;
use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\Contracts\VisualAdapterInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\ContentAI\Contracts\PromptExperimentRepositoryInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Infrastructure\ContentAI\Repositories\EloquentAIGenerationRepository;
use App\Infrastructure\ContentAI\Repositories\EloquentAISettingsRepository;
use App\Infrastructure\ContentAI\Repositories\EloquentGenerationFeedbackRepository;
use App\Infrastructure\ContentAI\Repositories\EloquentPromptExperimentRepository;
use App\Infrastructure\ContentAI\Repositories\EloquentPromptTemplateRepository;
use App\Application\AIIntelligence\Contracts\AudienceInsightAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Infrastructure\ContentAI\Services\EloquentPromptTemplateResolver;
use App\Infrastructure\ContentAI\Services\EloquentRAGContextProvider;
use App\Infrastructure\ContentAI\Services\LangGraphTextGenerator;
use App\Infrastructure\ContentAI\Services\LangGraphVisualAdapter;
use App\Infrastructure\ContentAI\Services\PrismTextGeneratorService;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Services\AiAgentsPlanGate;
use Illuminate\Support\ServiceProvider;

final class ContentAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIGenerationRepositoryInterface::class, EloquentAIGenerationRepository::class);
        $this->app->bind(AISettingsRepositoryInterface::class, EloquentAISettingsRepository::class);
        // PrismTextGeneratorService with enrichment providers
        $this->app->bind(PrismTextGeneratorService::class, function ($app) {
            return new PrismTextGeneratorService(
                ragContextProvider: $app->make(RAGContextProviderInterface::class),
                promptTemplateResolver: $app->make(PromptTemplateResolverInterface::class),
                styleProfileAnalyzer: $app->make(StyleProfileAnalyzerInterface::class),
                audienceInsightAnalyzer: $app->make(AudienceInsightAnalyzerInterface::class),
            );
        });

        $this->app->bind(TextGeneratorInterface::class, function ($app) {
            return new LangGraphTextGenerator(
                client: $app->make(LangGraphClientInterface::class),
                fallback: $app->make(PrismTextGeneratorService::class),
                planGate: $app->make(AiAgentsPlanGate::class),
            );
        });
        $this->app->bind(VisualAdapterInterface::class, LangGraphVisualAdapter::class);
        $this->app->bind(GenerationFeedbackRepositoryInterface::class, EloquentGenerationFeedbackRepository::class);
        $this->app->bind(PromptTemplateRepositoryInterface::class, EloquentPromptTemplateRepository::class);
        $this->app->bind(PromptExperimentRepositoryInterface::class, EloquentPromptExperimentRepository::class);
        $this->app->bind(PromptTemplateResolverInterface::class, EloquentPromptTemplateResolver::class);

        // RAGContextProvider depends on EmbeddingGeneratorInterface
        $this->app->bind(RAGContextProviderInterface::class, function ($app) {
            return new EloquentRAGContextProvider(
                embeddingGenerator: $app->make(EmbeddingGeneratorInterface::class),
            );
        });
    }
}
