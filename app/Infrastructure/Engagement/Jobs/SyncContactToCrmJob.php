<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Application\Engagement\DTOs\SyncContactToCrmInput;
use App\Application\Engagement\UseCases\SyncContactToCrmUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncContactToCrmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly SyncContactToCrmInput $input,
    ) {
        $this->onQueue('default');
    }

    public function handle(SyncContactToCrmUseCase $useCase): void
    {
        $useCase->execute($this->input);
    }
}
