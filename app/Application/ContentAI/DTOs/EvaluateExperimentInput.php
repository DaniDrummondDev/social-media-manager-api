<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class EvaluateExperimentInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $experimentId,
    ) {}
}
