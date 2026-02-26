<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum EmbeddingJobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed => true,
            default => false,
        };
    }
}
