<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Infrastructure\Engagement\Models\WebhookDeliveryModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RetryWebhookDeliveriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct()
    {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $pending = WebhookDeliveryModel::query()
            ->where('next_retry_at', '<=', now())
            ->whereNull('delivered_at')
            ->whereNull('failed_at')
            ->limit(100)
            ->get();

        /** @var WebhookDeliveryModel $delivery */
        foreach ($pending as $delivery) {
            DeliverWebhookJob::dispatch($delivery->getAttribute('id'));
        }
    }
}
