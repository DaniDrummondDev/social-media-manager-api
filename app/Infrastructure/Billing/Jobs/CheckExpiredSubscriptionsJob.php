<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Jobs;

use App\Infrastructure\Billing\Models\SubscriptionModel;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CheckExpiredSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('billing');
    }

    public function handle(): void
    {
        $now = new DateTimeImmutable;
        $pastDueThreshold = $now->modify('-7 days');

        $pastDueSubscriptions = SubscriptionModel::query()
            ->where('status', 'past_due')
            ->where('updated_at', '<', $pastDueThreshold->format('Y-m-d H:i:s'))
            ->get();

        /** @var SubscriptionModel $subscription */
        foreach ($pastDueSubscriptions as $subscription) {
            DowngradeToFreePlanJob::dispatch((string) $subscription->getAttribute('id'));
        }

        $canceledSubscriptions = SubscriptionModel::query()
            ->where('cancel_at_period_end', true)
            ->where('current_period_end', '<', $now->format('Y-m-d H:i:s'))
            ->whereIn('status', ['active', 'canceled'])
            ->get();

        /** @var SubscriptionModel $subscription */
        foreach ($canceledSubscriptions as $subscription) {
            DowngradeToFreePlanJob::dispatch((string) $subscription->getAttribute('id'));
        }
    }
}
