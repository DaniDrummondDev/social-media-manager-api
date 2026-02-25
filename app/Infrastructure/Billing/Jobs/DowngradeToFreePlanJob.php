<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Jobs;

use App\Application\Billing\UseCases\DowngradeToFreePlanUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class DowngradeToFreePlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $subscriptionId,
    ) {
        $this->onQueue('billing');
    }

    public function handle(DowngradeToFreePlanUseCase $useCase): void
    {
        $useCase->execute($this->subscriptionId);
    }
}
