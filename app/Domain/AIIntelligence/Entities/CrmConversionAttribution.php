<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\CrmConversionAttributed;
use App\Domain\AIIntelligence\ValueObjects\AttributionType;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class CrmConversionAttribution
{
    /**
     * @param  array<string, mixed>  $interactionData
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $crmConnectionId,
        public Uuid $contentId,
        public string $crmEntityType,
        public string $crmEntityId,
        public AttributionType $attributionType,
        public ?float $attributionValue,
        public ?string $currency,
        public ?string $crmStage,
        public array $interactionData,
        public DateTimeImmutable $attributedAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $interactionData
     */
    public static function create(
        Uuid $organizationId,
        Uuid $crmConnectionId,
        Uuid $contentId,
        string $crmEntityType,
        string $crmEntityId,
        AttributionType $attributionType,
        ?float $attributionValue,
        ?string $currency,
        ?string $crmStage,
        array $interactionData,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            crmConnectionId: $crmConnectionId,
            contentId: $contentId,
            crmEntityType: $crmEntityType,
            crmEntityId: $crmEntityId,
            attributionType: $attributionType,
            attributionValue: $attributionValue,
            currency: $currency,
            crmStage: $crmStage,
            interactionData: $interactionData,
            attributedAt: $now,
            createdAt: $now,
            domainEvents: [
                new CrmConversionAttributed(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    contentId: (string) $contentId,
                    crmEntityType: $crmEntityType,
                    attributionType: $attributionType->value,
                    attributionValue: $attributionValue,
                ),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $interactionData
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $crmConnectionId,
        Uuid $contentId,
        string $crmEntityType,
        string $crmEntityId,
        AttributionType $attributionType,
        ?float $attributionValue,
        ?string $currency,
        ?string $crmStage,
        array $interactionData,
        DateTimeImmutable $attributedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            crmConnectionId: $crmConnectionId,
            contentId: $contentId,
            crmEntityType: $crmEntityType,
            crmEntityId: $crmEntityId,
            attributionType: $attributionType,
            attributionValue: $attributionValue,
            currency: $currency,
            crmStage: $crmStage,
            interactionData: $interactionData,
            attributedAt: $attributedAt,
            createdAt: $createdAt,
        );
    }

    public function hasMonetaryValue(): bool
    {
        return $this->attributionType->hasMonetaryValue() && $this->attributionValue !== null && $this->attributionValue > 0;
    }
}
