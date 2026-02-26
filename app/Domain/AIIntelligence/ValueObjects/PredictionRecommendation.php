<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

final readonly class PredictionRecommendation
{
    private function __construct(
        public string $type,
        public string $message,
        public string $impactEstimate,
    ) {}

    public static function create(string $type, string $message, string $impactEstimate): self
    {
        return new self($type, $message, $impactEstimate);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            type: (string) $data['type'],
            message: (string) $data['message'],
            impactEstimate: (string) $data['impact_estimate'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'impact_estimate' => $this->impactEstimate,
        ];
    }
}
