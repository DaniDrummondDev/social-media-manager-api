<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class UpdateAudienceInput
{
    /**
     * @param  array<string, mixed>|null  $targetingSpec
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $audienceId,
        public ?string $name = null,
        public ?array $targetingSpec = null,
    ) {}
}
