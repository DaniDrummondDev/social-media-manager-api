<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

use App\Domain\AIIntelligence\Exceptions\InvalidCalendarItemException;

final readonly class CalendarItem
{
    /**
     * @param  array<string>  $topics
     * @param  array<string>  $targetNetworks
     */
    private function __construct(
        public string $date,
        public array $topics,
        public string $contentType,
        public array $targetNetworks,
        public string $reasoning,
        public int $priority,
    ) {}

    /**
     * @param  array<string>  $topics
     * @param  array<string>  $targetNetworks
     */
    public static function create(
        string $date,
        array $topics,
        string $contentType,
        array $targetNetworks,
        string $reasoning,
        int $priority,
    ): self {
        if ($topics === []) {
            throw new InvalidCalendarItemException('Calendar item requires at least one topic.');
        }

        if ($targetNetworks === []) {
            throw new InvalidCalendarItemException('Calendar item requires at least one target network.');
        }

        if ($priority < 1) {
            throw new InvalidCalendarItemException('Calendar item priority must be at least 1.');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new InvalidCalendarItemException('Calendar item date must be in YYYY-MM-DD format.');
        }

        return new self($date, $topics, $contentType, $targetNetworks, $reasoning, $priority);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            date: (string) $data['date'],
            topics: (array) $data['topics'],
            contentType: (string) $data['content_type'],
            targetNetworks: (array) $data['target_networks'],
            reasoning: (string) $data['reasoning'],
            priority: (int) $data['priority'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'topics' => $this->topics,
            'content_type' => $this->contentType,
            'target_networks' => $this->targetNetworks,
            'reasoning' => $this->reasoning,
            'priority' => $this->priority,
        ];
    }
}
