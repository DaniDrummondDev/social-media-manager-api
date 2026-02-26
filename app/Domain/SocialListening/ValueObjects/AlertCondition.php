<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

use App\Domain\SocialListening\Exceptions\InvalidAlertConditionException;

final readonly class AlertCondition
{
    private function __construct(
        public ConditionType $type,
        public int $threshold,
        public int $windowMinutes,
    ) {}

    public static function create(ConditionType $type, int $threshold, int $windowMinutes): self
    {
        if ($threshold < 1) {
            throw new InvalidAlertConditionException('Threshold deve ser maior que zero.');
        }

        if ($windowMinutes < 1) {
            throw new InvalidAlertConditionException('Window deve ser maior que zero minutos.');
        }

        return new self($type, $threshold, $windowMinutes);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            type: ConditionType::from($data['type']),
            threshold: (int) $data['threshold'],
            windowMinutes: (int) $data['window_minutes'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'threshold' => $this->threshold,
            'window_minutes' => $this->windowMinutes,
        ];
    }
}
