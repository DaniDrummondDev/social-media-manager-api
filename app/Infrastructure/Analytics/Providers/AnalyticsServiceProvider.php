<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Providers;

use App\Application\Analytics\Contracts\ReportGeneratorInterface;
use App\Application\Analytics\Contracts\SocialAnalyticsFactoryInterface;
use App\Domain\Analytics\Repositories\AccountMetricRepositoryInterface;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\Repositories\ContentMetricSnapshotRepositoryInterface;
use App\Domain\Analytics\Repositories\ReportExportRepositoryInterface;
use App\Domain\Publishing\Events\PostPublished;
use App\Infrastructure\Analytics\Listeners\ScheduleMetricsSyncOnPostPublished;
use App\Infrastructure\Analytics\Repositories\EloquentAccountMetricRepository;
use App\Infrastructure\Analytics\Repositories\EloquentContentMetricRepository;
use App\Infrastructure\Analytics\Repositories\EloquentContentMetricSnapshotRepository;
use App\Infrastructure\Analytics\Repositories\EloquentReportExportRepository;
use App\Infrastructure\Analytics\Services\SocialAnalyticsFactory;
use App\Infrastructure\Analytics\Services\StubReportGenerator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContentMetricRepositoryInterface::class, EloquentContentMetricRepository::class);
        $this->app->bind(ContentMetricSnapshotRepositoryInterface::class, EloquentContentMetricSnapshotRepository::class);
        $this->app->bind(AccountMetricRepositoryInterface::class, EloquentAccountMetricRepository::class);
        $this->app->bind(ReportExportRepositoryInterface::class, EloquentReportExportRepository::class);
        $this->app->bind(SocialAnalyticsFactoryInterface::class, SocialAnalyticsFactory::class);
        $this->app->bind(ReportGeneratorInterface::class, StubReportGenerator::class);
    }

    public function boot(): void
    {
        Event::listen(PostPublished::class, ScheduleMetricsSyncOnPostPublished::class);
    }
}
