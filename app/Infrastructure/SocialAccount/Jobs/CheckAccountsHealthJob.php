<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Jobs;

use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CheckAccountsHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SocialAccountRepositoryInterface $repository): void
    {
        $accounts = $repository->findExpiringTokens(minutesUntilExpiry: 360);

        foreach ($accounts as $account) {
            try {
                if ($account->credentials->isExpired()) {
                    $account->markTokenExpired();
                    $repository->update($account);

                    Log::info('Social account marked as token expired', [
                        'account_id' => (string) $account->id,
                        'provider' => $account->provider->value,
                    ]);
                }
            } catch (Throwable $e) {
                Log::warning('Failed to check health for social account', [
                    'account_id' => (string) $account->id,
                    'provider' => $account->provider->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
