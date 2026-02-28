<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Providers;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Application\Shared\Contracts\SentimentAnalyzerInterface;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Services\AiAgentsCircuitBreaker;
use App\Infrastructure\Shared\Services\EloquentTransactionManager;
use App\Infrastructure\Shared\Services\LangGraphClient;
use App\Infrastructure\Shared\Services\LaravelEventDispatcher;
use App\Infrastructure\Shared\Services\Sha256HashService;
use App\Infrastructure\Shared\Services\StubSentimentAnalyzer;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventDispatcherInterface::class, LaravelEventDispatcher::class);
        $this->app->bind(HashServiceInterface::class, Sha256HashService::class);
        $this->app->bind(SentimentAnalyzerInterface::class, StubSentimentAnalyzer::class);
        $this->app->bind(TransactionManagerInterface::class, EloquentTransactionManager::class);
        $this->app->bind(AiAgentsCircuitBreakerInterface::class, AiAgentsCircuitBreaker::class);
        $this->app->bind(LangGraphClientInterface::class, LangGraphClient::class);
    }
}
