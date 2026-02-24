<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\CampaignOutput;
use App\Application\Campaign\DTOs\CampaignStatsOutput;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetCampaignUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ContentRepositoryInterface $contentRepository,
    ) {}

    public function execute(string $organizationId, string $campaignId): CampaignOutput
    {
        $campaign = $this->campaignRepository->findById(Uuid::fromString($campaignId));

        if ($campaign === null || (string) $campaign->organizationId !== $organizationId || $campaign->isDeleted()) {
            throw new CampaignNotFoundException($campaignId);
        }

        $counts = $this->contentRepository->countByCampaignAndStatus($campaign->id);
        $stats = CampaignStatsOutput::fromCounts($counts);

        return CampaignOutput::fromEntity($campaign, $stats->toArray());
    }
}
