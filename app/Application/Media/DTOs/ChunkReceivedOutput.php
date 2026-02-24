<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class ChunkReceivedOutput
{
    public function __construct(
        public int $chunkIndex,
        public int $receivedCount,
        public int $totalChunks,
        public bool $allChunksReceived,
    ) {}
}
