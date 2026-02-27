<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Jobs;

use App\Application\PaidAdvertising\DTOs\SyncAdMetricsInput;
use App\Application\PaidAdvertising\UseCases\SyncAdMetricsUseCase;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncAdMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** @var array<int> */
    public array $backoff = [60];

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(
        AdBoostRepositoryInterface $boostRepository,
        SyncAdMetricsUseCase $useCase,
    ): void {
        $activeBoosts = $boostRepository->findByStatus(AdStatus::Active);

        foreach ($activeBoosts as $boost) {
            try {
                $useCase->execute(new SyncAdMetricsInput(
                    boostId: (string) $boost->id,
                ));
            } catch (\Throwable $e) {
                Log::warning('SyncAdMetricsJob: Failed to sync metrics for boost', [
                    'boost_id' => (string) $boost->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
