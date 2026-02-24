<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

enum Tone: string
{
    case Professional = 'professional';
    case Casual = 'casual';
    case Fun = 'fun';
    case Informative = 'informative';
    case Inspirational = 'inspirational';
    case Custom = 'custom';
}
