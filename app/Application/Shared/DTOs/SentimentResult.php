<?php

declare(strict_types=1);

namespace App\Application\Shared\DTOs;

final readonly class SentimentResult
{
    public function __construct(
        public string $sentiment,
        public float $score,
    ) {}
}
