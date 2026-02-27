<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Providers;

use App\Application\PaidAdvertising\Contracts\AdOAuthStateServiceInterface;
use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdReportExporterInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Domain\PaidAdvertising\Events\AdMetricsSynced;
use App\Domain\PaidAdvertising\Events\BoostCreated;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Infrastructure\PaidAdvertising\Listeners\DispatchBoostSubmission;
use App\Infrastructure\PaidAdvertising\Listeners\ScheduleAdPerformanceAggregation;
use App\Infrastructure\PaidAdvertising\Repositories\EloquentAdAccountRepository;
use App\Infrastructure\PaidAdvertising\Repositories\EloquentAdBoostRepository;
use App\Infrastructure\PaidAdvertising\Repositories\EloquentAdMetricSnapshotRepository;
use App\Infrastructure\PaidAdvertising\Repositories\EloquentAudienceRepository;
use App\Infrastructure\PaidAdvertising\Services\AdOAuthStateService;
use App\Infrastructure\PaidAdvertising\Services\AdPlatformFactory;
use App\Infrastructure\PaidAdvertising\Services\AdTokenEncrypter;
use App\Infrastructure\PaidAdvertising\Services\StubAdReportExporter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class PaidAdvertisingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(AdAccountRepositoryInterface::class, EloquentAdAccountRepository::class);
        $this->app->bind(AudienceRepositoryInterface::class, EloquentAudienceRepository::class);
        $this->app->bind(AdBoostRepositoryInterface::class, EloquentAdBoostRepository::class);
        $this->app->bind(AdMetricSnapshotRepositoryInterface::class, EloquentAdMetricSnapshotRepository::class);

        // Application Contracts
        $this->app->bind(AdPlatformFactoryInterface::class, AdPlatformFactory::class);
        $this->app->bind(AdOAuthStateServiceInterface::class, AdOAuthStateService::class);
        $this->app->bind(AdTokenEncryptorInterface::class, AdTokenEncrypter::class);
        $this->app->bind(AdReportExporterInterface::class, StubAdReportExporter::class);
    }

    public function boot(): void
    {
        Event::listen(BoostCreated::class, DispatchBoostSubmission::class);
        Event::listen(AdMetricsSynced::class, ScheduleAdPerformanceAggregation::class);
    }
}
