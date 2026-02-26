<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Providers;

use App\Application\SocialListening\Contracts\SocialListeningAdapterInterface;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Infrastructure\SocialListening\Adapters\SocialListeningAdapterFactory;
use App\Infrastructure\SocialListening\Repositories\EloquentListeningAlertRepository;
use App\Infrastructure\SocialListening\Repositories\EloquentListeningQueryRepository;
use App\Infrastructure\SocialListening\Repositories\EloquentListeningReportRepository;
use App\Infrastructure\SocialListening\Repositories\EloquentMentionRepository;
use Illuminate\Support\ServiceProvider;

final class SocialListeningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ListeningQueryRepositoryInterface::class, EloquentListeningQueryRepository::class);
        $this->app->bind(MentionRepositoryInterface::class, EloquentMentionRepository::class);
        $this->app->bind(ListeningAlertRepositoryInterface::class, EloquentListeningAlertRepository::class);
        $this->app->bind(ListeningReportRepositoryInterface::class, EloquentListeningReportRepository::class);
        $this->app->bind(SocialListeningAdapterFactory::class);
    }

    public function boot(): void
    {
        //
    }
}
