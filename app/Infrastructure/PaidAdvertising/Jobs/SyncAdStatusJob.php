<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Jobs;

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncAdStatusJob implements ShouldQueue
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
        AdAccountRepositoryInterface $accountRepository,
        AdPlatformFactoryInterface $platformFactory,
        AdTokenEncryptorInterface $tokenEncryptor,
    ): void {
        $pendingBoosts = $boostRepository->findByStatus(AdStatus::PendingReview);
        $activeBoosts = $boostRepository->findByStatus(AdStatus::Active);
        $boosts = array_merge($pendingBoosts, $activeBoosts);

        foreach ($boosts as $boost) {
            try {
                $adAccount = $accountRepository->findById($boost->adAccountId);

                if ($adAccount === null || ! $adAccount->isOperational()) {
                    continue;
                }

                if ($boost->externalIds === null) {
                    continue;
                }

                $adapter = $platformFactory->make($adAccount->provider);
                $decryptedToken = $tokenEncryptor->decrypt($adAccount->credentials->encryptedAccessToken);

                $statusData = $adapter->getAdStatus(
                    $decryptedToken,
                    $adAccount->providerAccountId,
                    $boost->externalIds['ad_id'] ?? '',
                );

                $effectiveStatus = $statusData['effective_status'] ?? '';

                if ($effectiveStatus === 'DISAPPROVED') {
                    $reason = $statusData['review_feedback'] ?? 'Rejected by platform';
                    $updated = $boost->reject($reason, (string) $boost->createdBy);
                    $boostRepository->update($updated);

                    continue;
                }

                if (in_array($effectiveStatus, ['CAMPAIGN_PAUSED', 'ADSET_PAUSED'], true)) {
                    continue;
                }

                if ($effectiveStatus === 'ACTIVE' && $boost->status === AdStatus::PendingReview) {
                    $updated = $boost->activate($boost->externalIds, (string) $boost->createdBy);
                    $boostRepository->update($updated);
                }
            } catch (\Throwable $e) {
                Log::warning('SyncAdStatusJob: Failed to sync boost status', [
                    'boost_id' => (string) $boost->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
