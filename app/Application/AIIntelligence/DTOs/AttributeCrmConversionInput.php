<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class AttributeCrmConversionInput
{
    /**
     * @param  array<string, mixed>  $interactionData
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $crmConnectionId,
        public string $contentId,
        public string $crmEntityType,
        public string $crmEntityId,
        public string $attributionType,
        public ?float $attributionValue = null,
        public ?string $currency = null,
        public ?string $crmStage = null,
        public array $interactionData = [],
    ) {}
}
