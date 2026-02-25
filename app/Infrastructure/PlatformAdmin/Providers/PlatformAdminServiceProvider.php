<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Providers;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Repositories\AdminAuditEntryRepositoryInterface;
use App\Domain\PlatformAdmin\Repositories\PlatformAdminRepositoryInterface;
use App\Domain\PlatformAdmin\Repositories\PlatformMetricsCacheRepositoryInterface;
use App\Domain\PlatformAdmin\Repositories\SystemConfigRepositoryInterface;
use App\Infrastructure\PlatformAdmin\Repositories\EloquentAdminAuditEntryRepository;
use App\Infrastructure\PlatformAdmin\Repositories\EloquentPlatformAdminRepository;
use App\Infrastructure\PlatformAdmin\Repositories\EloquentPlatformMetricsCacheRepository;
use App\Infrastructure\PlatformAdmin\Repositories\EloquentSystemConfigRepository;
use App\Infrastructure\PlatformAdmin\Services\AuditService;
use App\Infrastructure\PlatformAdmin\Services\EloquentPlatformQueryService;
use Illuminate\Support\ServiceProvider;

final class PlatformAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlatformAdminRepositoryInterface::class, EloquentPlatformAdminRepository::class);
        $this->app->bind(SystemConfigRepositoryInterface::class, EloquentSystemConfigRepository::class);
        $this->app->bind(AdminAuditEntryRepositoryInterface::class, EloquentAdminAuditEntryRepository::class);
        $this->app->bind(PlatformMetricsCacheRepositoryInterface::class, EloquentPlatformMetricsCacheRepository::class);
        $this->app->bind(AuditServiceInterface::class, AuditService::class);
        $this->app->bind(PlatformQueryServiceInterface::class, EloquentPlatformQueryService::class);
    }
}
