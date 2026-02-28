<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\ContentListOutput;
use App\Application\Campaign\DTOs\ContentOutput;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListContentsUseCase
{
    public function __construct(
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function execute(string $organizationId, string $campaignId): ContentListOutput
    {
        $campaign = $this->campaignRepository->findById(Uuid::fromString($campaignId));

        if ($campaign === null || (string) $campaign->organizationId !== $organizationId || $campaign->isDeleted()) {
            throw new CampaignNotFoundException($campaignId);
        }

        $contents = $this->contentRepository->findByCampaignId(
            Uuid::fromString($campaignId),
        );

        $outputs = array_map(
            fn ($content) => ContentOutput::fromEntity($content),
            $contents,
        );

        return new ContentListOutput(items: $outputs);
    }
}
