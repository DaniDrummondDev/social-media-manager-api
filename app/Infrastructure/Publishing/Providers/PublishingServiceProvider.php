<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Providers;

use App\Application\Publishing\Contracts\SocialPublisherFactoryInterface;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Infrastructure\Publishing\Repositories\EloquentScheduledPostRepository;
use App\Infrastructure\Publishing\Services\SocialPublisherFactory;
use Illuminate\Support\ServiceProvider;

final class PublishingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScheduledPostRepositoryInterface::class, EloquentScheduledPostRepository::class);
        $this->app->bind(SocialPublisherFactoryInterface::class, SocialPublisherFactory::class);
    }
}
