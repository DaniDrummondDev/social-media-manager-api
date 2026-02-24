<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Providers;

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Infrastructure\Campaign\Repositories\EloquentCampaignRepository;
use App\Infrastructure\Campaign\Repositories\EloquentContentMediaRepository;
use App\Infrastructure\Campaign\Repositories\EloquentContentNetworkOverrideRepository;
use App\Infrastructure\Campaign\Repositories\EloquentContentRepository;
use Illuminate\Support\ServiceProvider;

final class CampaignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CampaignRepositoryInterface::class, EloquentCampaignRepository::class);
        $this->app->bind(ContentRepositoryInterface::class, EloquentContentRepository::class);
        $this->app->bind(ContentNetworkOverrideRepositoryInterface::class, EloquentContentNetworkOverrideRepository::class);
        $this->app->bind(ContentMediaRepositoryInterface::class, EloquentContentMediaRepository::class);
    }
}
