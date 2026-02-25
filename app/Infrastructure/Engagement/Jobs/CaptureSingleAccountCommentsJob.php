<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Jobs;

use App\Application\Engagement\DTOs\CaptureCommentsInput;
use App\Application\Engagement\UseCases\CaptureCommentsUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CaptureSingleAccountCommentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $socialAccountId,
    ) {
        $this->onQueue('engagement');
    }

    public function handle(CaptureCommentsUseCase $useCase): void
    {
        $useCase->execute(new CaptureCommentsInput(
            socialAccountId: $this->socialAccountId,
        ));
    }
}
