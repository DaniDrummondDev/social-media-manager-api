<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

final readonly class SentimentBreakdown
{
    private function __construct(
        public int $positive,
        public int $neutral,
        public int $negative,
    ) {}

    public static function create(int $positive, int $neutral, int $negative): self
    {
        return new self($positive, $neutral, $negative);
    }

    public static function empty(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            positive: (int) ($data['positive'] ?? 0),
            neutral: (int) ($data['neutral'] ?? 0),
            negative: (int) ($data['negative'] ?? 0),
        );
    }

    public function total(): int
    {
        return $this->positive + $this->neutral + $this->negative;
    }

    public function dominantSentiment(): ?Sentiment
    {
        if ($this->total() === 0) {
            return null;
        }

        $max = max($this->positive, $this->neutral, $this->negative);

        return match ($max) {
            $this->positive => Sentiment::Positive,
            $this->negative => Sentiment::Negative,
            default => Sentiment::Neutral,
        };
    }

    public function negativePercentage(): float
    {
        $total = $this->total();

        if ($total === 0) {
            return 0.0;
        }

        return ($this->negative / $total) * 100;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'positive' => $this->positive,
            'neutral' => $this->neutral,
            'negative' => $this->negative,
        ];
    }
}
