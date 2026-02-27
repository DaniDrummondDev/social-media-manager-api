<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class CreateAudienceInput
{
    /**
     * @param  array<string, mixed>  $targetingSpec
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $name,
        public array $targetingSpec,
    ) {}
}
