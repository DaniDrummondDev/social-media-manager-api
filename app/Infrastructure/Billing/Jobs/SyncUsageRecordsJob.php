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

    private const int CHUNK_SIZE = 200;

    /**
     * Resource types mapped to their source tables and whether they need soft-delete filtering.
     *
     * @var array<string, array{table: string, column: string, soft_delete: bool}>
     */
    private const array RESOURCE_MAP = [
        'members' => ['table' => 'organization_members', 'column' => 'organization_id', 'soft_delete' => false],
        'social_accounts' => ['table' => 'social_accounts', 'column' => 'organization_id', 'soft_delete' => false],
        'campaigns' => ['table' => 'campaigns', 'column' => 'organization_id', 'soft_delete' => false],
        'automations' => ['table' => 'automation_rules', 'column' => 'organization_id', 'soft_delete' => true],
        'webhooks' => ['table' => 'webhook_endpoints', 'column' => 'organization_id', 'soft_delete' => true],
    ];

    public function __construct()
    {
        $this->onQueue('billing');
    }

    public function handle(): void
    {
        $periodStart = new DateTimeImmutable('first day of this month midnight');
        $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

        SubscriptionModel::query()
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->chunkById(self::CHUNK_SIZE, function ($subscriptions) use ($periodStart, $periodEnd): void {
                /** @var SubscriptionModel $subscription */
                foreach ($subscriptions as $subscription) {
                    $orgId = (string) $subscription->getAttribute('organization_id');
                    $this->syncAllCountersForOrganization($orgId, $periodStart, $periodEnd);
                }
            });
    }

    private function syncAllCountersForOrganization(
        string $orgId,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
    ): void {
        foreach (self::RESOURCE_MAP as $resourceType => $config) {
            $query = DB::table($config['table'])->where($config['column'], $orgId);

            if ($config['soft_delete']) {
                $query->whereNull('deleted_at');
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
}
