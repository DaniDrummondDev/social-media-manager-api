<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum Sentiment: string
{
    case Positive = 'positive';
    case Neutral = 'neutral';
    case Negative = 'negative';
}
