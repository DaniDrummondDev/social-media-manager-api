<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

use App\Domain\AIIntelligence\Exceptions\InvalidTimeSlotException;

final readonly class TimeSlotScore
{
    private function __construct(
        public int $day,
        public int $hour,
        public int $score,
    ) {}

    public static function create(int $day, int $hour, int $score): self
    {
        if ($day < 0 || $day > 6) {
            throw new InvalidTimeSlotException('Day must be between 0 (Sunday) and 6 (Saturday).');
        }

        if ($hour < 0 || $hour > 23) {
            throw new InvalidTimeSlotException('Hour must be between 0 and 23.');
        }

        if ($score < 0 || $score > 100) {
            throw new InvalidTimeSlotException('Score must be between 0 and 100.');
        }

        return new self($day, $hour, $score);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            day: (int) $data['day'],
            hour: (int) $data['hour'],
            score: (int) $data['score'],
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'day' => $this->day,
            'hour' => $this->hour,
            'score' => $this->score,
        ];
    }
}
