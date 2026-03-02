<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Providers;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Application\Shared\Contracts\SentimentAnalyzerInterface;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Documentation\ApiResponseExtension;
use App\Infrastructure\Shared\Documentation\JwtSecurityExtension;
use App\Infrastructure\Shared\Documentation\RouteTagExtension;
use App\Infrastructure\Shared\Services\AiAgentsCircuitBreaker;
use App\Infrastructure\Shared\Services\EloquentTransactionManager;
use App\Infrastructure\Shared\Services\LangGraphClient;
use App\Infrastructure\Shared\Services\LaravelEventDispatcher;
use App\Infrastructure\Shared\Services\Sha256HashService;
use App\Infrastructure\Shared\Services\StubSentimentAnalyzer;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
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

    public function boot(): void
    {
        $this->configureScramble();
    }

    private function configureScramble(): void
    {
        if (! class_exists(Scramble::class)) {
            return;
        }

        Scramble::routes(function (Route $route): bool {
            $uri = $route->uri();

            if (str_contains($uri, 'internal/')) {
                return false;
            }

            return str_starts_with($uri, 'api/v1');
        });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT'),
            );
        });

        Scramble::registerExtensions([
            ApiResponseExtension::class,
            JwtSecurityExtension::class,
            RouteTagExtension::class,
        ]);
    }
}
