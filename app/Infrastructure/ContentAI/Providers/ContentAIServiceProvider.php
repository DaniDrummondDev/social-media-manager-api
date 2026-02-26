<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Providers;

use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\Contracts\RAGContextProviderInterface;
use App\Application\ContentAI\Contracts\TextGeneratorInterface;
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
use App\Infrastructure\ContentAI\Services\PrismTextGeneratorService;
use App\Infrastructure\ContentAI\Services\StubPromptTemplateResolver;
use App\Infrastructure\ContentAI\Services\StubRAGContextProvider;
use Illuminate\Support\ServiceProvider;

final class ContentAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIGenerationRepositoryInterface::class, EloquentAIGenerationRepository::class);
        $this->app->bind(AISettingsRepositoryInterface::class, EloquentAISettingsRepository::class);
        $this->app->bind(TextGeneratorInterface::class, PrismTextGeneratorService::class);
        $this->app->bind(GenerationFeedbackRepositoryInterface::class, EloquentGenerationFeedbackRepository::class);
        $this->app->bind(PromptTemplateRepositoryInterface::class, EloquentPromptTemplateRepository::class);
        $this->app->bind(PromptExperimentRepositoryInterface::class, EloquentPromptExperimentRepository::class);
        $this->app->bind(PromptTemplateResolverInterface::class, StubPromptTemplateResolver::class);
        $this->app->bind(RAGContextProviderInterface::class, StubRAGContextProvider::class);
    }
}
