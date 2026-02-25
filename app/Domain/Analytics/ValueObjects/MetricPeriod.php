<?php

declare(strict_types=1);

namespace App\Domain\Analytics\ValueObjects;

use App\Domain\Analytics\Exceptions\InvalidMetricPeriodException;
use DateTimeImmutable;

final readonly class MetricPeriod
{
    private function __construct(
        public string $type,
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {}

    public static function fromPreset(string $preset): self
    {
        $to = new DateTimeImmutable('today 23:59:59');

        $from = match ($preset) {
            '7d' => new DateTimeImmutable('-7 days 00:00:00'),
            '30d' => new DateTimeImmutable('-30 days 00:00:00'),
            '90d' => new DateTimeImmutable('-90 days 00:00:00'),
            default => throw new InvalidMetricPeriodException("Unknown preset: {$preset}"),
        };

        return new self($preset, $from, $to);
    }

    public static function custom(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        if ($from > $to) {
            throw new InvalidMetricPeriodException('Start date must be before or equal to end date.');
        }

        return new self('custom', $from, $to);
    }

    public function previousPeriod(): self
    {
        $diff = $this->from->diff($this->to);
        $previousTo = $this->from->modify('-1 second');
        $previousFrom = $previousTo->sub($diff);

        return new self('comparison', $previousFrom, $previousTo);
    }

    public function days(): int
    {
        return (int) $this->from->diff($this->to)->days + 1;
    }
}
