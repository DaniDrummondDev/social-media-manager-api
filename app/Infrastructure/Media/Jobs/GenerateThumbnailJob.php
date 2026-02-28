<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        private readonly string $mediaId,
    ) {}

    public function handle(): void
    {
        // TODO: Implement thumbnail generation with GD/Imagick for images, ffmpeg for videos
        Log::info('GenerateThumbnailJob: stub — not implemented yet', [
            'media_id' => $this->mediaId,
        ]);
    }
}
