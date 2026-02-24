<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\DeleteCampaignInput;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteCampaignUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * @return array{cancelled_schedules: int, purge_at: string}
     */
    public function execute(DeleteCampaignInput $input): array
    {
        $campaign = $this->campaignRepository->findById(Uuid::fromString($input->campaignId));

        if ($campaign === null || (string) $campaign->organizationId !== $input->organizationId || $campaign->isDeleted()) {
            throw new CampaignNotFoundException($input->campaignId);
        }

        $campaign = $campaign->softDelete();
        $this->campaignRepository->update($campaign);

        return [
            'cancelled_schedules' => 0,
            'purge_at' => $campaign->purgeAt?->format('c') ?? '',
        ];
    }
}
