<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Providers;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\Contracts\PasswordResetServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Infrastructure\Identity\Repositories\EloquentRefreshTokenRepository;
use App\Infrastructure\Identity\Repositories\EloquentUserRepository;
use App\Infrastructure\Identity\Services\EmailVerificationService;
use App\Infrastructure\Identity\Services\JwtAuthTokenService;
use App\Infrastructure\Identity\Services\PasswordResetService;
use App\Infrastructure\Identity\Services\TwoFactorService;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(AuthTokenServiceInterface::class, JwtAuthTokenService::class);
        $this->app->bind(RefreshTokenRepositoryInterface::class, EloquentRefreshTokenRepository::class);
        $this->app->bind(EmailVerificationServiceInterface::class, EmailVerificationService::class);
        $this->app->bind(PasswordResetServiceInterface::class, PasswordResetService::class);
        $this->app->bind(TwoFactorServiceInterface::class, TwoFactorService::class);
    }
}
