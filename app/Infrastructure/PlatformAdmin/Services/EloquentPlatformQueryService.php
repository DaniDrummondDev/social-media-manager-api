<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Services;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentPlatformQueryService implements PlatformQueryServiceInterface
{
    // ─── Read: Dashboard Metrics ─────────────────────────────────

    /**
     * @return array{total: int, active: int, suspended: int}
     */
    public function countOrganizations(): array
    {
        $counts = DB::table('organizations')
            ->selectRaw("count(*) as total")
            ->selectRaw("count(*) filter (where status = 'active') as active")
            ->selectRaw("count(*) filter (where status = 'suspended') as suspended")
            ->first();

        return [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'suspended' => (int) $counts->suspended,
        ];
    }

    /**
     * @return array{total: int, active_30d: int}
     */
    public function countUsers(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $counts = DB::table('users')
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where last_login_at > ?) as active_30d', [$thirtyDaysAgo])
            ->first();

        return [
            'total' => (int) $counts->total,
            'active_30d' => (int) $counts->active_30d,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function countSubscriptionsByPlan(): array
    {
        $rows = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->selectRaw('plans.slug, count(*) as total')
            ->groupBy('plans.slug')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->slug] = (int) $row->total;
        }

        return $result;
    }

    /**
     * @return array{trialing: int, past_due: int}
     */
    public function countSubscriptionsByStatus(): array
    {
        $counts = DB::table('subscriptions')
            ->selectRaw("count(*) filter (where status = 'trialing') as trialing")
            ->selectRaw("count(*) filter (where status = 'past_due') as past_due")
            ->first();

        return [
            'trialing' => (int) $counts->trialing,
            'past_due' => (int) $counts->past_due,
        ];
    }

    public function calculateMrrCents(): int
    {
        $monthly = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->where('subscriptions.billing_cycle', 'monthly')
            ->sum('plans.price_monthly_cents');

        $yearly = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->where('subscriptions.billing_cycle', 'yearly')
            ->selectRaw('coalesce(sum(plans.price_yearly_cents / 12), 0) as mrr')
            ->value('mrr');

        return (int) $monthly + (int) $yearly;
    }

    public function calculateArrCents(): int
    {
        return $this->calculateMrrCents() * 12;
    }

    /**
     * @return array{publications: int, ai_generations: int}
     */
    public function getUsageToday(): array
    {
        $today = Carbon::today()->toDateString();

        $counts = DB::table('usage_records')
            ->where('period_start', '<=', $today)
            ->where('period_end', '>=', $today)
            ->selectRaw("coalesce(sum(quantity) filter (where resource_type = 'publications'), 0) as publications")
            ->selectRaw("coalesce(sum(quantity) filter (where resource_type = 'ai_generations'), 0) as ai_generations")
            ->first();

        return [
            'publications' => (int) $counts->publications,
            'ai_generations' => (int) $counts->ai_generations,
        ];
    }

    public function getStorageUsedGb(): float
    {
        $bytes = DB::table('usage_records')
            ->where('resource_type', 'storage_bytes')
            ->sum('quantity');

        return round((float) $bytes / (1024 * 1024 * 1024), 2);
    }

    public function countActiveSocialAccounts(): int
    {
        return (int) DB::table('social_accounts')
            ->where('status', 'connected')
            ->count();
    }

    /**
     * @return array{success_rate: float, avg_latency_ms: int, providers: array<string, string>}
     */
    public function getPublishingHealth24h(): array
    {
        $since = Carbon::now()->subHours(24);

        $stats = DB::table('scheduled_posts')
            ->where('created_at', '>=', $since)
            ->selectRaw('count(*) as total')
            ->selectRaw("count(*) filter (where status = 'published') as success")
            ->first();

        $total = (int) $stats->total;
        $success = (int) $stats->success;
        $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 100.0;

        $providers = DB::table('scheduled_posts')
            ->join('social_accounts', 'scheduled_posts.social_account_id', '=', 'social_accounts.id')
            ->where('scheduled_posts.created_at', '>=', $since)
            ->selectRaw('social_accounts.provider, count(*) as total')
            ->selectRaw("count(*) filter (where scheduled_posts.status = 'failed') as failed")
            ->groupBy('social_accounts.provider')
            ->get();

        $providerHealth = [];
        foreach ($providers as $provider) {
            $pTotal = (int) $provider->total;
            $pFailed = (int) $provider->failed;
            $providerHealth[$provider->provider] = $pFailed === 0 ? 'healthy' : ($pFailed < $pTotal ? 'degraded' : 'down');
        }

        return [
            'success_rate' => $successRate,
            'avg_latency_ms' => 0,
            'providers' => $providerHealth,
        ];
    }

    // ─── Read: Organization Queries ──────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<array<string, mixed>>, next_cursor: ?string, has_more: bool}
     */
    public function listOrganizations(array $filters, int $perPage, ?string $cursor): array
    {
        $query = DB::table('organizations')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('organizations.id', '=', 'subscriptions.organization_id')
                    ->where('subscriptions.status', 'active');
            })
            ->leftJoin('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select([
                'organizations.id',
                'organizations.name',
                'organizations.slug',
                'organizations.status',
                'organizations.created_at',
                'plans.slug as plan_slug',
                'plans.name as plan_name',
            ]);

        if (isset($filters['status'])) {
            $query->where('organizations.status', $filters['status']);
        }

        if (isset($filters['plan_slug'])) {
            $query->where('plans.slug', $filters['plan_slug']);
        }

        if (isset($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('organizations.name', 'LIKE', $search)
                    ->orWhere('organizations.slug', 'LIKE', $search);
            });
        }

        if (isset($filters['from'])) {
            $query->where('organizations.created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('organizations.created_at', '<=', $filters['to']);
        }

        if ($cursor !== null) {
            $query->where('organizations.id', '<', $cursor);
        }

        $records = $query->orderByDesc('organizations.id')
            ->limit($perPage + 1)
            ->get();

        $hasMore = $records->count() > $perPage;

        if ($hasMore) {
            $records = $records->slice(0, $perPage);
        }

        $items = $records->map(function ($row) {
            $memberCount = DB::table('organization_members')
                ->where('organization_id', $row->id)
                ->count();

            return [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'status' => $row->status,
                'plan_slug' => $row->plan_slug,
                'plan_name' => $row->plan_name,
                'member_count' => $memberCount,
                'created_at' => $row->created_at,
            ];
        })->values()->all();

        $nextCursor = null;
        if ($hasMore && $records->isNotEmpty()) {
            $lastRecord = $records->last();
            $nextCursor = (string) $lastRecord->id;
        }

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrganizationDetail(string $organizationId): ?array
    {
        $org = DB::table('organizations')->where('id', $organizationId)->first();

        if ($org === null) {
            return null;
        }

        $members = DB::table('organization_members')
            ->join('users', 'organization_members.user_id', '=', 'users.id')
            ->where('organization_members.organization_id', $organizationId)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'organization_members.role',
                'organization_members.joined_at',
            ])
            ->get()
            ->toArray();

        $subscription = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.organization_id', $organizationId)
            ->select([
                'subscriptions.id',
                'subscriptions.status',
                'subscriptions.billing_cycle',
                'subscriptions.current_period_start',
                'subscriptions.current_period_end',
                'subscriptions.trial_ends_at',
                'plans.name as plan_name',
                'plans.slug as plan_slug',
            ])
            ->orderByDesc('subscriptions.created_at')
            ->first();

        $socialAccounts = DB::table('social_accounts')
            ->where('organization_id', $organizationId)
            ->select(['id', 'provider', 'platform_username', 'status', 'created_at'])
            ->get()
            ->toArray();

        $usageRecords = DB::table('usage_records')
            ->where('organization_id', $organizationId)
            ->select(['resource_type', 'quantity', 'period_start', 'period_end'])
            ->orderByDesc('period_start')
            ->limit(20)
            ->get()
            ->toArray();

        return [
            'id' => $org->id,
            'name' => $org->name,
            'slug' => $org->slug,
            'status' => $org->status,
            'timezone' => $org->timezone ?? null,
            'created_at' => $org->created_at,
            'updated_at' => $org->updated_at,
            'members' => $members,
            'subscription' => $subscription,
            'social_accounts' => $socialAccounts,
            'usage' => $usageRecords,
        ];
    }

    // ─── Read: User Queries ──────────────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<array<string, mixed>>, next_cursor: ?string, has_more: bool}
     */
    public function listUsers(array $filters, int $perPage, ?string $cursor): array
    {
        $query = DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.status',
                'users.email_verified_at',
                'users.last_login_at',
                'users.created_at',
            ]);

        if (isset($filters['status'])) {
            $query->where('users.status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'LIKE', $search)
                    ->orWhere('users.email', 'LIKE', $search);
            });
        }

        if (isset($filters['from'])) {
            $query->where('users.created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('users.created_at', '<=', $filters['to']);
        }

        if ($cursor !== null) {
            $query->where('users.id', '<', $cursor);
        }

        $records = $query->orderByDesc('users.id')
            ->limit($perPage + 1)
            ->get();

        $hasMore = $records->count() > $perPage;

        if ($hasMore) {
            $records = $records->slice(0, $perPage);
        }

        $items = $records->map(function ($row) {
            $orgCount = DB::table('organization_members')
                ->where('user_id', $row->id)
                ->count();

            return [
                'id' => $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'status' => $row->status,
                'email_verified' => $row->email_verified_at !== null,
                'last_login_at' => $row->last_login_at,
                'organization_count' => $orgCount,
                'created_at' => $row->created_at,
            ];
        })->values()->all();

        $nextCursor = null;
        if ($hasMore && $records->isNotEmpty()) {
            $lastRecord = $records->last();
            $nextCursor = (string) $lastRecord->id;
        }

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUserDetail(string $userId): ?array
    {
        $user = DB::table('users')
            ->where('id', $userId)
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'timezone',
                'status',
                'email_verified_at',
                'two_factor_enabled',
                'last_login_at',
                'last_login_ip',
                'created_at',
                'updated_at',
            ])
            ->first();

        if ($user === null) {
            return null;
        }

        $organizations = DB::table('organization_members')
            ->join('organizations', 'organization_members.organization_id', '=', 'organizations.id')
            ->where('organization_members.user_id', $userId)
            ->select([
                'organizations.id',
                'organizations.name',
                'organizations.slug',
                'organizations.status',
                'organization_members.role',
                'organization_members.joined_at',
            ])
            ->get()
            ->toArray();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'timezone' => $user->timezone,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at,
            'two_factor_enabled' => (bool) $user->two_factor_enabled,
            'last_login_at' => $user->last_login_at,
            'last_login_ip' => $user->last_login_ip,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'organizations' => $organizations,
        ];
    }

    // ─── Read: Plan Queries ──────────────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<array<string, mixed>>, next_cursor: ?string, has_more: bool}
     */
    public function listPlanSubscribers(string $planId, array $filters, int $perPage, ?string $cursor): array
    {
        $query = DB::table('subscriptions')
            ->join('organizations', 'subscriptions.organization_id', '=', 'organizations.id')
            ->where('subscriptions.plan_id', $planId)
            ->select([
                'organizations.id',
                'organizations.name',
                'organizations.slug',
                'organizations.status as org_status',
                'subscriptions.status as sub_status',
                'subscriptions.billing_cycle',
                'subscriptions.created_at',
            ]);

        if (isset($filters['status'])) {
            $query->where('subscriptions.status', $filters['status']);
        }

        if ($cursor !== null) {
            $query->where('organizations.id', '<', $cursor);
        }

        $records = $query->orderByDesc('organizations.id')
            ->limit($perPage + 1)
            ->get();

        $hasMore = $records->count() > $perPage;

        if ($hasMore) {
            $records = $records->slice(0, $perPage);
        }

        $items = $records->map(fn ($row) => [
            'organization_id' => $row->id,
            'organization_name' => $row->name,
            'organization_slug' => $row->slug,
            'organization_status' => $row->org_status,
            'subscription_status' => $row->sub_status,
            'billing_cycle' => $row->billing_cycle,
            'created_at' => $row->created_at,
        ])->values()->all();

        $nextCursor = null;
        if ($hasMore && $records->isNotEmpty()) {
            $lastRecord = $records->last();
            $nextCursor = (string) $lastRecord->id;
        }

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    public function countPlanSubscribers(string $planId): int
    {
        return (int) DB::table('subscriptions')
            ->where('plan_id', $planId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function listAllPlans(): array
    {
        return DB::table('plans')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('plans.id', '=', 'subscriptions.plan_id')
                    ->where('subscriptions.status', 'active');
            })
            ->select([
                'plans.id',
                'plans.name',
                'plans.slug',
                'plans.description',
                'plans.price_monthly_cents',
                'plans.price_yearly_cents',
                'plans.currency',
                'plans.limits',
                'plans.features',
                'plans.is_active',
                'plans.sort_order',
                'plans.created_at',
                'plans.updated_at',
            ])
            ->selectRaw('count(subscriptions.id) as subscriber_count')
            ->groupBy(
                'plans.id',
                'plans.name',
                'plans.slug',
                'plans.description',
                'plans.price_monthly_cents',
                'plans.price_yearly_cents',
                'plans.currency',
                'plans.limits',
                'plans.features',
                'plans.is_active',
                'plans.sort_order',
                'plans.created_at',
                'plans.updated_at',
            )
            ->orderBy('plans.sort_order')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'description' => $row->description,
                'price_monthly_cents' => (int) $row->price_monthly_cents,
                'price_yearly_cents' => (int) $row->price_yearly_cents,
                'currency' => $row->currency,
                'limits' => is_string($row->limits) ? json_decode($row->limits, true) : $row->limits,
                'features' => is_string($row->features) ? json_decode($row->features, true) : $row->features,
                'is_active' => (bool) $row->is_active,
                'sort_order' => (int) $row->sort_order,
                'subscriber_count' => (int) $row->subscriber_count,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ])
            ->all();
    }

    // ─── Read: Admin Info ────────────────────────────────────────

    /**
     * @return array{id: string, name: string, email: string}|null
     */
    public function getAdminInfo(string $adminId): ?array
    {
        $record = DB::table('platform_admins')
            ->join('users', 'platform_admins.user_id', '=', 'users.id')
            ->where('platform_admins.id', $adminId)
            ->select([
                'platform_admins.id',
                'users.name',
                'users.email',
            ])
            ->first();

        if ($record === null) {
            return null;
        }

        return [
            'id' => $record->id,
            'name' => $record->name,
            'email' => $record->email,
        ];
    }

    // ─── Mutation: Organization ──────────────────────────────────

    public function suspendOrganization(string $id, string $reason): void
    {
        DB::table('organizations')
            ->where('id', $id)
            ->update([
                'status' => 'suspended',
                'suspended_at' => Carbon::now(),
                'suspension_reason' => $reason,
                'updated_at' => Carbon::now(),
            ]);
    }

    public function unsuspendOrganization(string $id): void
    {
        DB::table('organizations')
            ->where('id', $id)
            ->update([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
                'updated_at' => Carbon::now(),
            ]);
    }

    public function deleteOrganization(string $id): void
    {
        DB::table('organizations')
            ->where('id', $id)
            ->update([
                'status' => 'deleted',
                'deleted_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }

    // ─── Mutation: User ──────────────────────────────────────────

    public function banUser(string $id, string $reason): void
    {
        DB::table('users')
            ->where('id', $id)
            ->update([
                'status' => 'suspended',
                'banned_at' => Carbon::now(),
                'ban_reason' => $reason,
                'updated_at' => Carbon::now(),
            ]);
    }

    public function unbanUser(string $id): void
    {
        DB::table('users')
            ->where('id', $id)
            ->update([
                'status' => 'active',
                'banned_at' => null,
                'ban_reason' => null,
                'updated_at' => Carbon::now(),
            ]);
    }

    public function forceVerifyUser(string $id): void
    {
        DB::table('users')
            ->where('id', $id)
            ->update([
                'email_verified_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }

    // ─── Mutation: Plan ──────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPlan(array $data): string
    {
        $id = Str::uuid()->toString();

        if (isset($data['limits']) && is_array($data['limits'])) {
            $data['limits'] = json_encode($data['limits']);
        }

        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        DB::table('plans')->insert(array_merge($data, [
            'id' => $id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]));

        return $id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePlan(string $planId, array $data): void
    {
        DB::table('plans')
            ->where('id', $planId)
            ->update(array_merge($data, [
                'updated_at' => Carbon::now(),
            ]));
    }

    public function deactivatePlan(string $planId): void
    {
        DB::table('plans')
            ->where('id', $planId)
            ->update([
                'is_active' => false,
                'updated_at' => Carbon::now(),
            ]);
    }
}
