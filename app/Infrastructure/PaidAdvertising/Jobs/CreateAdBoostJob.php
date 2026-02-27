<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Jobs;

use App\Application\PaidAdvertising\DTOs\SubmitBoostToPlatformInput;
use App\Application\PaidAdvertising\UseCases\SubmitBoostToPlatformUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreateAdBoostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(
        public readonly string $boostId,
    ) {
        $this->onQueue('high');
    }

    public function handle(SubmitBoostToPlatformUseCase $useCase): void
    {
        $useCase->execute(new SubmitBoostToPlatformInput(
            boostId: $this->boostId,
        ));
    }
}
