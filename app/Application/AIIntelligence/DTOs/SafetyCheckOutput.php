<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\BrandSafetyCheck;

final readonly class SafetyCheckOutput
{
    /**
     * @param  array<array<string, mixed>>  $checks
     */
    public function __construct(
        public string $id,
        public string $contentId,
        public ?string $provider,
        public string $overallStatus,
        public ?int $overallScore,
        public array $checks,
        public ?string $modelUsed,
        public ?string $checkedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(BrandSafetyCheck $check): self
    {
        return new self(
            id: (string) $check->id,
            contentId: (string) $check->contentId,
            provider: $check->provider,
            overallStatus: $check->overallStatus->value,
            overallScore: $check->overallScore,
            checks: array_map(fn ($c) => $c->toArray(), $check->checks),
            modelUsed: $check->modelUsed,
            checkedAt: $check->checkedAt?->format('c'),
            createdAt: $check->createdAt->format('c'),
        );
    }
}
