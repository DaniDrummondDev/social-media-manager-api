<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class SentimentTrendOutput
{
    public function __construct(
        public string $date,
        public int $positive,
        public int $neutral,
        public int $negative,
        public int $total,
    ) {}
}
