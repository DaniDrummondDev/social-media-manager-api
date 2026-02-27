<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Jobs;

use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class RefreshAdAccountTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** @var array<int> */
    public array $backoff = [120];

    public function __construct()
    {
        $this->onQueue('high');
    }

    public function handle(
        AdAccountRepositoryInterface $accountRepository,
    ): void {
        $expiringAccounts = $accountRepository->findExpiringTokens(120);

        foreach ($expiringAccounts as $account) {
            Log::warning('RefreshAdAccountTokenJob: Ad account token expiring soon', [
                'account_id' => (string) $account->id,
                'organization_id' => (string) $account->organizationId,
                'provider' => $account->provider->value,
            ]);
        }
    }
}
