<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Providers;

use App\Application\SocialAccount\Contracts\OAuthStateServiceInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Infrastructure\SocialAccount\Repositories\EloquentSocialAccountRepository;
use App\Infrastructure\SocialAccount\Services\RedisOAuthStateService;
use App\Infrastructure\SocialAccount\Services\SocialAdapterFactory;
use App\Infrastructure\SocialAccount\Services\SocialTokenEncrypter;
use Illuminate\Support\ServiceProvider;

final class SocialAccountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SocialTokenEncrypter::class);

        $this->app->bind(SocialAccountRepositoryInterface::class, EloquentSocialAccountRepository::class);
        $this->app->bind(OAuthStateServiceInterface::class, RedisOAuthStateService::class);
        $this->app->bind(SocialAccountAdapterFactoryInterface::class, SocialAdapterFactory::class);
    }
}
