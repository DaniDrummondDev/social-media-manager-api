<?php

declare(strict_types=1);

namespace App\Domain\Publishing\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ScheduleTime
{
    private function __construct(
        public DateTimeImmutable $dateTime,
    ) {}

    public static function forFuture(DateTimeImmutable $dateTime, int $minMinutes = 5): self
    {
        $now = new DateTimeImmutable;
        $minTime = $now->modify("+{$minMinutes} minutes");

        if ($dateTime < $minTime) {
            throw new InvalidArgumentException(
                "Scheduled time must be at least {$minMinutes} minutes in the future.",
            );
        }

        return new self($dateTime);
    }

    public static function forImmediate(): self
    {
        return new self(new DateTimeImmutable);
    }

    public static function fromDateTimeImmutable(DateTimeImmutable $dateTime): self
    {
        return new self($dateTime);
    }

    public function toDateTimeImmutable(): DateTimeImmutable
    {
        return $this->dateTime;
    }
}
