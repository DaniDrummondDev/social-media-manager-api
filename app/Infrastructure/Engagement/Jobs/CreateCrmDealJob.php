<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Application\Engagement\DTOs\CreateCrmDealInput;
use App\Application\Engagement\UseCases\CreateCrmDealUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreateCrmDealJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly CreateCrmDealInput $input,
    ) {
        $this->onQueue('default');
    }

    public function handle(CreateCrmDealUseCase $useCase): void
    {
        $useCase->execute($this->input);
    }
}
