<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('database.default') !== 'sqlite') {
            $this->app->register(\Pgvector\Laravel\PgvectorServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth.login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('auth.register', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('auth.2fa', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('auth.password', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('auth.refresh', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
