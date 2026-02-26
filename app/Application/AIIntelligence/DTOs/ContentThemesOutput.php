<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class ContentThemesOutput
{
    /**
     * @param  array<array{theme: string, score: float, content_count: int}>  $themes
     */
    public function __construct(
        public array $themes,
    ) {}
}
