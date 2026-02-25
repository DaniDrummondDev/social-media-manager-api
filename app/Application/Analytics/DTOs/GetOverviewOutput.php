<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class GetOverviewOutput
{
    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $comparison
     * @param  array<string, array<string, mixed>>  $byNetwork
     * @param  array<int, array<string, mixed>>  $trend
     * @param  array<int, array<string, mixed>>  $topContents
     */
    public function __construct(
        public string $period,
        public array $summary,
        public array $comparison,
        public array $byNetwork,
        public array $trend,
        public array $topContents,
    ) {}
}
