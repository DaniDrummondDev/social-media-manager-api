<?php

declare(strict_types=1);

namespace App\Application\ContentAI\Services;

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Exceptions\CampaignBriefRequiredException;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class BriefContextResolver
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function resolve(
        string $generationMode,
        ?string $campaignId,
        string $organizationId,
        string $topic,
    ): string {
        if ($generationMode === 'fields_only') {
            return $topic;
        }

        $campaign = $this->campaignRepository->findById(Uuid::fromString($campaignId));

        if ($campaign === null || (string) $campaign->organizationId !== $organizationId) {
            throw new CampaignNotFoundException($campaignId);
        }

        if ($campaign->brief === null || $campaign->brief->isEmpty()) {
            throw new CampaignBriefRequiredException((string) $campaign->id);
        }

        $briefContext = $campaign->brief->toPromptContext();

        if ($generationMode === 'brief_only') {
            return $briefContext;
        }

        return $briefContext . "\n\n[USER TOPIC]\n" . $topic;
    }
}
