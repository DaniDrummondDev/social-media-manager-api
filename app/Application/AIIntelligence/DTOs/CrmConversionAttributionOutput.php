<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\CrmConversionAttribution;

final readonly class CrmConversionAttributionOutput
{
    /**
     * @param  array<string, mixed>  $interactionData
     */
    public function __construct(
        public string $id,
        public string $crmConnectionId,
        public string $contentId,
        public string $crmEntityType,
        public string $crmEntityId,
        public string $attributionType,
        public ?float $attributionValue,
        public ?string $currency,
        public ?string $crmStage,
        public array $interactionData,
        public string $attributedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(CrmConversionAttribution $attribution): self
    {
        return new self(
            id: (string) $attribution->id,
            crmConnectionId: (string) $attribution->crmConnectionId,
            contentId: (string) $attribution->contentId,
            crmEntityType: $attribution->crmEntityType,
            crmEntityId: $attribution->crmEntityId,
            attributionType: $attribution->attributionType->value,
            attributionValue: $attribution->attributionValue,
            currency: $attribution->currency,
            crmStage: $attribution->crmStage,
            interactionData: $attribution->interactionData,
            attributedAt: $attribution->attributedAt->format('c'),
            createdAt: $attribution->createdAt->format('c'),
        );
    }
}
