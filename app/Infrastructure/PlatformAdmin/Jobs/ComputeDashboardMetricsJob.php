<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Jobs;

use App\Infrastructure\PlatformAdmin\Models\PlatformMetricsCacheModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class ComputeDashboardMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const int CACHE_TTL_SECONDS = 300;

    public function __construct()
    {
        $this->onQueue('admin');
    }

    public function handle(): void
    {
        $this->computeOverview();
        $this->computeSubscriptions();
        $this->computeUsage();
        $this->computeHealth();
    }

    private function computeOverview(): void
    {
        $orgs = DB::table('organizations')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'active') as active")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'suspended') as suspended")
            ->first();

        $users = DB::table('users')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COUNT(*) FILTER (WHERE last_login_at >= ?) as active_30d", [now()->subDays(30)])
            ->first();

        $this->storeCache('dashboard_overview', [
            'organizations' => [
                'total' => (int) $orgs->total,
                'active' => (int) $orgs->active,
                'suspended' => (int) $orgs->suspended,
            ],
            'users' => [
                'total' => (int) $users->total,
                'active_30d' => (int) $users->active_30d,
            ],
        ]);
    }

    private function computeSubscriptions(): void
    {
        $byPlan = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->groupBy('plans.slug')
            ->selectRaw('plans.slug, COUNT(*) as count')
            ->pluck('count', 'slug')
            ->toArray();

        $byStatus = DB::table('subscriptions')
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'trialing') as trialing")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'past_due') as past_due")
            ->first();

        $mrr = (int) DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->sum('plans.price_monthly_amount_cents');

        $this->storeCache('dashboard_subscriptions', [
            'mrr_cents' => $mrr,
            'arr_cents' => $mrr * 12,
            'by_plan' => $byPlan,
            'by_status' => [
                'trialing' => (int) ($byStatus->trialing ?? 0),
                'past_due' => (int) ($byStatus->past_due ?? 0),
            ],
        ]);
    }

    private function computeUsage(): void
    {
        $publications = (int) DB::table('scheduled_posts')
            ->where('status', 'published')
            ->whereDate('published_at', today())
            ->count();

        $aiGenerations = (int) DB::table('ai_generation_history')
            ->whereDate('created_at', today())
            ->count();

        $storageGb = round(
            (float) DB::table('media')->sum('file_size') / (1024 * 1024 * 1024),
            2,
        );

        $activeSocialAccounts = (int) DB::table('social_accounts')
            ->where('status', 'active')
            ->count();

        $this->storeCache('dashboard_usage', [
            'publications' => $publications,
            'ai_generations' => $aiGenerations,
            'storage_gb' => $storageGb,
            'active_social_accounts' => $activeSocialAccounts,
        ]);
    }

    private function computeHealth(): void
    {
        $total = (int) DB::table('scheduled_posts')
            ->where('published_at', '>=', now()->subHours(24))
            ->count();

        $success = (int) DB::table('scheduled_posts')
            ->where('published_at', '>=', now()->subHours(24))
            ->where('status', 'published')
            ->count();

        $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 100.0;

        $avgLatency = (int) DB::table('scheduled_posts')
            ->where('published_at', '>=', now()->subHours(24))
            ->where('status', 'published')
            ->avg(DB::raw("EXTRACT(EPOCH FROM (published_at - scheduled_at)) * 1000"));

        $providers = DB::table('social_accounts')
            ->where('status', 'active')
            ->groupBy('provider')
            ->selectRaw("provider, 'healthy' as status")
            ->pluck('status', 'provider')
            ->toArray();

        $this->storeCache('dashboard_health', [
            'success_rate' => $successRate,
            'avg_latency_ms' => $avgLatency,
            'providers' => $providers,
        ]);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function storeCache(string $key, array $value): void
    {
        PlatformMetricsCacheModel::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'computed_at' => now(),
                'ttl_seconds' => self::CACHE_TTL_SECONDS,
            ],
        );
    }
}
