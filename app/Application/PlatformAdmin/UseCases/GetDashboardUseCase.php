<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\DashboardOutput;
use App\Domain\PlatformAdmin\Repositories\PlatformMetricsCacheRepositoryInterface;

final class GetDashboardUseCase
{
    private const int CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly PlatformMetricsCacheRepositoryInterface $cache,
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    public function execute(): DashboardOutput
    {
        $overview = $this->getCachedOrCompute('dashboard_overview', function (): array {
            return [
                'organizations' => $this->queryService->countOrganizations(),
                'users' => $this->queryService->countUsers(),
            ];
        });

        $subscriptions = $this->getCachedOrCompute('dashboard_subscriptions', function (): array {
            return [
                'mrr_cents' => $this->queryService->calculateMrrCents(),
                'arr_cents' => $this->queryService->calculateArrCents(),
                'by_plan' => $this->queryService->countSubscriptionsByPlan(),
                'by_status' => $this->queryService->countSubscriptionsByStatus(),
            ];
        });

        $usage = $this->getCachedOrCompute('dashboard_usage', function (): array {
            $usageToday = $this->queryService->getUsageToday();

            return [
                'publications' => $usageToday['publications'],
                'ai_generations' => $usageToday['ai_generations'],
                'storage_gb' => $this->queryService->getStorageUsedGb(),
                'active_social_accounts' => $this->queryService->countActiveSocialAccounts(),
            ];
        });

        $health = $this->getCachedOrCompute('dashboard_health', function (): array {
            return $this->queryService->getPublishingHealth24h();
        });

        return new DashboardOutput(
            overview: $overview,
            subscriptions: $subscriptions,
            usage: $usage,
            health: $health,
            generatedAt: (new \DateTimeImmutable)->format('Y-m-d\TH:i:s\Z'),
        );
    }

    /**
     * @param  callable(): array<string, mixed>  $compute
     * @return array<string, mixed>
     */
    private function getCachedOrCompute(string $key, callable $compute): array
    {
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $compute();
        $this->cache->set($key, $value, self::CACHE_TTL_SECONDS);

        return $value;
    }
}
