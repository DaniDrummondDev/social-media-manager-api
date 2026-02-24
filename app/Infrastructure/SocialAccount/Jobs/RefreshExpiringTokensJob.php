<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Jobs;

use App\Application\SocialAccount\DTOs\RefreshSocialTokenInput;
use App\Application\SocialAccount\UseCases\RefreshSocialTokenUseCase;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RefreshExpiringTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $minutesUntilExpiry = 60,
    ) {}

    public function handle(
        SocialAccountRepositoryInterface $repository,
        RefreshSocialTokenUseCase $useCase,
    ): void {
        $accounts = $repository->findExpiringTokens($this->minutesUntilExpiry);

        foreach ($accounts as $account) {
            try {
                $useCase->execute(new RefreshSocialTokenInput(
                    organizationId: (string) $account->organizationId,
                    accountId: (string) $account->id,
                ));
            } catch (Throwable $e) {
                Log::warning('Failed to refresh token for social account', [
                    'account_id' => (string) $account->id,
                    'provider' => $account->provider->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
