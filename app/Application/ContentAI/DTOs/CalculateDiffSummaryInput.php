<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class CalculateDiffSummaryInput
{
    public function __construct(
        public string $feedbackId,
    ) {}
}
