<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

use App\Domain\AIIntelligence\Exceptions\InvalidTimeSlotException;

final readonly class TopSlot
{
    private function __construct(
        public int $day,
        public string $dayName,
        public int $hour,
        public float $avgEngagementRate,
        public int $sampleSize,
    ) {}

    private const array DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public static function create(int $day, int $hour, float $avgEngagementRate, int $sampleSize): self
    {
        if ($day < 0 || $day > 6) {
            throw new InvalidTimeSlotException('Day must be between 0 (Sunday) and 6 (Saturday).');
        }

        if ($hour < 0 || $hour > 23) {
            throw new InvalidTimeSlotException('Hour must be between 0 and 23.');
        }

        return new self(
            day: $day,
            dayName: self::DAY_NAMES[$day],
            hour: $hour,
            avgEngagementRate: $avgEngagementRate,
            sampleSize: $sampleSize,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            day: (int) $data['day'],
            hour: (int) $data['hour'],
            avgEngagementRate: (float) $data['avg_engagement_rate'],
            sampleSize: (int) $data['sample_size'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'day' => $this->day,
            'day_name' => $this->dayName,
            'hour' => $this->hour,
            'avg_engagement_rate' => $this->avgEngagementRate,
            'sample_size' => $this->sampleSize,
        ];
    }
}
