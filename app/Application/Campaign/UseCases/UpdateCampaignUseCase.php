<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\CampaignOutput;
use App\Application\Campaign\DTOs\UpdateCampaignInput;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Campaign\Exceptions\DuplicateCampaignNameException;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class UpdateCampaignUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function execute(UpdateCampaignInput $input): CampaignOutput
    {
        $campaign = $this->campaignRepository->findById(Uuid::fromString($input->campaignId));

        if ($campaign === null || (string) $campaign->organizationId !== $input->organizationId || $campaign->isDeleted()) {
            throw new CampaignNotFoundException($input->campaignId);
        }

        if ($input->name !== null && strtolower($input->name) !== strtolower($campaign->name)) {
            if ($this->campaignRepository->existsByOrganizationAndName(
                $campaign->organizationId,
                $input->name,
                $campaign->id,
            )) {
                throw new DuplicateCampaignNameException($input->name);
            }
        }

        $brief = null;
        if ($input->clearBrief) {
            $brief = new CampaignBrief(null, null, null, null);
        } elseif ($input->briefText !== null || $input->briefTargetAudience !== null || $input->briefRestrictions !== null || $input->briefCta !== null) {
            $inputBrief = new CampaignBrief(
                text: $input->briefText,
                targetAudience: $input->briefTargetAudience,
                restrictions: $input->briefRestrictions,
                cta: $input->briefCta,
            );
            $brief = $campaign->brief !== null ? $campaign->brief->mergeWith($inputBrief) : $inputBrief;
        }

        $campaign = $campaign->update(
            name: $input->name,
            description: $input->description,
            startsAt: $input->startsAt !== null ? new DateTimeImmutable($input->startsAt) : null,
            endsAt: $input->endsAt !== null ? new DateTimeImmutable($input->endsAt) : null,
            tags: $input->tags,
            status: $input->status !== null ? CampaignStatus::from($input->status) : null,
            brief: $brief,
        );

        $this->campaignRepository->update($campaign);

        return CampaignOutput::fromEntity($campaign);
    }
}
