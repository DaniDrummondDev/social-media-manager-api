<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Application\Engagement\UseCases\RefreshCrmTokenUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RefreshCrmTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** @var array<int> */
    public array $backoff = [60, 300];

    public function __construct(
        public readonly string $connectionId,
    ) {
        $this->onQueue('high');
    }

    public function handle(RefreshCrmTokenUseCase $useCase): void
    {
        $useCase->execute($this->connectionId);
    }
}
