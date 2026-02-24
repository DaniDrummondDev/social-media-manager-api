<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Jobs;

use App\Application\Media\DTOs\ScanMediaInput;
use App\Application\Media\UseCases\ScanMediaUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ScanMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $mediaId,
        private readonly string $scanResult,
    ) {}

    public function handle(ScanMediaUseCase $useCase): void
    {
        $useCase->execute(new ScanMediaInput(
            mediaId: $this->mediaId,
            scanResult: $this->scanResult,
        ));
    }
}
