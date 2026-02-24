<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final readonly class DateRange
{
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
        if ($start > $end) {
            throw new DomainException('Start date must be before end date.');
        }
    }

    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->start && $date <= $this->end;
    }

    public function overlaps(self $other): bool
    {
        return $this->start <= $other->end && $this->end >= $other->start;
    }
}
