<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

use App\Domain\PaidAdvertising\Entities\Audience;

final readonly class AudienceOutput
{
    /**
     * @param  array<string, mixed>  $targetingSpec
     * @param  array<string, string>|null  $providerAudienceIds
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public array $targetingSpec,
        public ?array $providerAudienceIds,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Audience $audience): self
    {
        return new self(
            id: (string) $audience->id,
            organizationId: (string) $audience->organizationId,
            name: $audience->name,
            targetingSpec: $audience->targetingSpec->toArray(),
            providerAudienceIds: $audience->providerAudienceIds,
            createdAt: $audience->createdAt->format('c'),
            updatedAt: $audience->updatedAt->format('c'),
        );
    }
}
