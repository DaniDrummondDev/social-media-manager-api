<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum ConfidenceLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public static function fromSampleSize(int $sampleSize): self
    {
        return match (true) {
            $sampleSize > 50 => self::High,
            $sampleSize >= 20 => self::Medium,
            default => self::Low,
        };
    }
}
