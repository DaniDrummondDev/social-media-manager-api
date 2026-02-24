<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Providers;

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Infrastructure\ContentAI\Repositories\EloquentAIGenerationRepository;
use App\Infrastructure\ContentAI\Repositories\EloquentAISettingsRepository;
use App\Infrastructure\ContentAI\Services\PrismTextGeneratorService;
use Illuminate\Support\ServiceProvider;

final class ContentAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIGenerationRepositoryInterface::class, EloquentAIGenerationRepository::class);
        $this->app->bind(AISettingsRepositoryInterface::class, EloquentAISettingsRepository::class);
        $this->app->bind(TextGeneratorInterface::class, PrismTextGeneratorService::class);
    }
}
