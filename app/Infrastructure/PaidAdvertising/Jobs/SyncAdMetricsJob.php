<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Jobs;

use App\Application\PaidAdvertising\DTOs\SyncAdMetricsInput;
use App\Application\PaidAdvertising\UseCases\SyncAdMetricsUseCase;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Infrastructure\PaidAdvertising\Models\AdBoostModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncAdMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(SyncAdMetricsUseCase $useCase): void
    {
        AdBoostModel::query()
            ->where('status', AdStatus::Active->value)
            ->chunkById(200, function ($boosts) use ($useCase): void {
                /** @var AdBoostModel $boost */
                foreach ($boosts as $boost) {
                    try {
                        $useCase->execute(new SyncAdMetricsInput(
                            boostId: (string) $boost->getAttribute('id'),
                        ));
                    } catch (\Throwable $e) {
                        Log::warning('SyncAdMetricsJob: Failed to sync metrics for boost', [
                            'boost_id' => (string) $boost->getAttribute('id'),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
