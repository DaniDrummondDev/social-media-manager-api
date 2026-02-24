<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Resources;

use App\Application\Media\DTOs\ChunkReceivedOutput;

final readonly class ChunkReceivedResource
{
    private function __construct(
        private int $chunkIndex,
        private int $receivedCount,
        private int $totalChunks,
        private bool $allChunksReceived,
    ) {}

    public static function fromOutput(ChunkReceivedOutput $output): self
    {
        return new self(
            chunkIndex: $output->chunkIndex,
            receivedCount: $output->receivedCount,
            totalChunks: $output->totalChunks,
            allChunksReceived: $output->allChunksReceived,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'chunk_index' => $this->chunkIndex,
            'received_count' => $this->receivedCount,
            'total_chunks' => $this->totalChunks,
            'all_chunks_received' => $this->allChunksReceived,
        ];
    }
}
