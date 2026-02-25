<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Jobs;

use App\Infrastructure\Billing\Models\SubscriptionModel;
use App\Infrastructure\Billing\Models\UsageRecordModel;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SyncUsageRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('billing');
    }

    public function handle(): void
    {
        $periodStart = new DateTimeImmutable('first day of this month midnight');
        $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

        $activeSubscriptions = SubscriptionModel::query()
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->get();

        /** @var SubscriptionModel $subscription */
        foreach ($activeSubscriptions as $subscription) {
            $orgId = (string) $subscription->getAttribute('organization_id');

            $this->syncAbsoluteCounter($orgId, 'members', 'organization_members', 'organization_id', $periodStart, $periodEnd);
            $this->syncAbsoluteCounter($orgId, 'social_accounts', 'social_accounts', 'organization_id', $periodStart, $periodEnd);
            $this->syncAbsoluteCounter($orgId, 'campaigns', 'campaigns', 'organization_id', $periodStart, $periodEnd);
            $this->syncAbsoluteCounter($orgId, 'automations', 'automation_rules', 'organization_id', $periodStart, $periodEnd, 'deleted_at IS NULL');
            $this->syncAbsoluteCounter($orgId, 'webhooks', 'webhook_endpoints', 'organization_id', $periodStart, $periodEnd, 'deleted_at IS NULL');
        }
    }

    private function syncAbsoluteCounter(
        string $orgId,
        string $resourceType,
        string $table,
        string $orgColumn,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        ?string $extraCondition = null,
    ): void {
        $query = DB::table($table)->where($orgColumn, $orgId);
        if ($extraCondition !== null) {
            $query->whereRaw($extraCondition);
        }
        $count = (int) $query->count();

        UsageRecordModel::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'resource_type' => $resourceType,
                'period_start' => $periodStart->format('Y-m-d'),
            ],
            [
                'id' => (string) Str::uuid(),
                'quantity' => $count,
                'period_end' => $periodEnd->format('Y-m-d'),
                'recorded_at' => now(),
            ],
        );
    }
}
