<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\CampaignListOutput;
use App\Application\Campaign\DTOs\CampaignOutput;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListCampaignsUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function execute(string $organizationId): CampaignListOutput
    {
        $campaigns = $this->campaignRepository->findByOrganizationId(
            Uuid::fromString($organizationId),
        );

        $outputs = array_map(
            fn ($campaign) => CampaignOutput::fromEntity($campaign),
            $campaigns,
        );

        return new CampaignListOutput(items: $outputs);
    }
}
