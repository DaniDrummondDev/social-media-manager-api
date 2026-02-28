<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Jobs;

use App\Application\Billing\DTOs\ProcessStripeWebhookInput;
use App\Application\Billing\UseCases\ProcessStripeWebhookUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessStripeWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        private readonly string $payload,
        private readonly string $signature,
    ) {
        $this->onQueue('billing');
    }

    public function handle(ProcessStripeWebhookUseCase $useCase): void
    {
        $useCase->execute(new ProcessStripeWebhookInput(
            payload: $this->payload,
            signature: $this->signature,
        ));
    }
}
