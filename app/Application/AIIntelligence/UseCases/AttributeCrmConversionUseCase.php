<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\AttributeCrmConversionInput;
use App\Application\AIIntelligence\DTOs\CrmConversionAttributionOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\CrmConversionAttribution;
use App\Domain\AIIntelligence\Repositories\CrmConversionAttributionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AttributionType;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DomainException;

final class AttributeCrmConversionUseCase
{
    public function __construct(
        private readonly CrmConversionAttributionRepositoryInterface $attributionRepository,
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(AttributeCrmConversionInput $input): CrmConversionAttributionOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $crmConnectionId = Uuid::fromString($input->crmConnectionId);
        $contentId = Uuid::fromString($input->contentId);

        $connection = $this->connectionRepository->findById($crmConnectionId);
        if ($connection === null) {
            throw new DomainException("CRM connection not found: {$input->crmConnectionId}");
        }

        $attribution = CrmConversionAttribution::create(
            organizationId: $organizationId,
            crmConnectionId: $crmConnectionId,
            contentId: $contentId,
            crmEntityType: $input->crmEntityType,
            crmEntityId: $input->crmEntityId,
            attributionType: AttributionType::from($input->attributionType),
            attributionValue: $input->attributionValue,
            currency: $input->currency,
            crmStage: $input->crmStage,
            interactionData: $input->interactionData,
            userId: $input->userId,
        );

        $this->attributionRepository->create($attribution);
        $this->eventDispatcher->dispatch(...$attribution->domainEvents);

        return CrmConversionAttributionOutput::fromEntity($attribution);
    }
}
