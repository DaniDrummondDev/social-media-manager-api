<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

enum ExperimentStatus: string
{
    case Draft = 'draft';
    case Running = 'running';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Running, self::Canceled]),
            self::Running => in_array($target, [self::Completed, self::Canceled]),
            self::Completed, self::Canceled => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Canceled]);
    }
}
