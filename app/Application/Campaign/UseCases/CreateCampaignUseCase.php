<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\CampaignOutput;
use App\Application\Campaign\DTOs\CreateCampaignInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Exceptions\DuplicateCampaignNameException;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class CreateCampaignUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateCampaignInput $input): CampaignOutput
    {
        if ($this->campaignRepository->existsByOrganizationAndName(
            Uuid::fromString($input->organizationId),
            $input->name,
        )) {
            throw new DuplicateCampaignNameException($input->name);
        }

        $brief = null;
        if ($input->briefText !== null || $input->briefTargetAudience !== null || $input->briefRestrictions !== null || $input->briefCta !== null) {
            $brief = new CampaignBrief(
                text: $input->briefText,
                targetAudience: $input->briefTargetAudience,
                restrictions: $input->briefRestrictions,
                cta: $input->briefCta,
            );
        }

        $campaign = Campaign::create(
            organizationId: Uuid::fromString($input->organizationId),
            createdBy: Uuid::fromString($input->userId),
            name: $input->name,
            description: $input->description,
            startsAt: $input->startsAt !== null ? new DateTimeImmutable($input->startsAt) : null,
            endsAt: $input->endsAt !== null ? new DateTimeImmutable($input->endsAt) : null,
            tags: $input->tags,
            brief: $brief,
        );

        $this->campaignRepository->create($campaign);
        $this->eventDispatcher->dispatch(...$campaign->domainEvents);

        return CampaignOutput::fromEntity($campaign);
    }
}
