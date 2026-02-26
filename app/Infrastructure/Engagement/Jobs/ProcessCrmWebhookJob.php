<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Application\Engagement\UseCases\ProcessCrmWebhookUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessCrmWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $connectionId,
        public readonly string $eventType,
        public readonly array $payload,
    ) {
        $this->onQueue('default');
    }

    public function handle(ProcessCrmWebhookUseCase $useCase): void
    {
        $useCase->execute(
            $this->organizationId,
            $this->connectionId,
            $this->eventType,
            $this->payload,
        );
    }
}
