<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class PredictPerformanceInput
{
    /**
     * @param  array<string>  $providers
     */
    public function __construct(
        public string $organizationId,
        public string $contentId,
        public array $providers,
        public bool $detailed = false,
        public string $userId = 'system',
    ) {}
}
