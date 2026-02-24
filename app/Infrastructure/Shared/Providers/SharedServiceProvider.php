<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Providers;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Infrastructure\Shared\Services\LaravelEventDispatcher;
use App\Infrastructure\Shared\Services\Sha256HashService;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventDispatcherInterface::class, LaravelEventDispatcher::class);
        $this->app->bind(HashServiceInterface::class, Sha256HashService::class);
    }
}
