<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class RestoreCampaignUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function execute(string $organizationId, string $campaignId): void
    {
        $campaign = $this->campaignRepository->findById(Uuid::fromString($campaignId));

        if ($campaign === null || (string) $campaign->organizationId !== $organizationId) {
            throw new CampaignNotFoundException($campaignId);
        }

        if (! $campaign->isDeleted()) {
            return;
        }

        $campaign = $campaign->restore();
        $this->campaignRepository->update($campaign);
    }
}
