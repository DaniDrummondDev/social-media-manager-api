<?php

declare(strict_types=1);

namespace App\Application\Shared\Contracts;

use App\Application\Shared\DTOs\SentimentResult;

interface SentimentAnalyzerInterface
{
    public function analyze(string $text): SentimentResult;
}
