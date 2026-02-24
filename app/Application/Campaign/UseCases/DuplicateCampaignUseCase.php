<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\CampaignOutput;
use App\Application\Campaign\DTOs\DuplicateCampaignInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Entities\ContentNetworkOverride;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class DuplicateCampaignUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly ContentNetworkOverrideRepositoryInterface $overrideRepository,
        private readonly ContentMediaRepositoryInterface $contentMediaRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DuplicateCampaignInput $input): CampaignOutput
    {
        $original = $this->campaignRepository->findById(Uuid::fromString($input->campaignId));

        if ($original === null || (string) $original->organizationId !== $input->organizationId || $original->isDeleted()) {
            throw new CampaignNotFoundException($input->campaignId);
        }

        $newName = $input->name ?? $original->name.' (Copia)';

        $newCampaign = Campaign::create(
            organizationId: $original->organizationId,
            createdBy: Uuid::fromString($input->userId),
            name: $newName,
            description: $original->description,
            startsAt: $original->startsAt,
            endsAt: $original->endsAt,
            tags: $original->tags,
        );

        $this->campaignRepository->create($newCampaign);

        $contents = $this->contentRepository->findByCampaignId($original->id);

        foreach ($contents as $content) {
            $newContent = Content::create(
                organizationId: $content->organizationId,
                campaignId: $newCampaign->id,
                createdBy: Uuid::fromString($input->userId),
                title: $content->title,
                body: $content->body,
                hashtags: $content->hashtags,
            );

            $this->contentRepository->create($newContent);

            $overrides = $this->overrideRepository->findByContentId($content->id);
            if ($overrides !== []) {
                $newOverrides = array_map(
                    fn (ContentNetworkOverride $o) => ContentNetworkOverride::create(
                        contentId: $newContent->id,
                        provider: $o->provider,
                        title: $o->title,
                        body: $o->body,
                        hashtags: $o->hashtags,
                        metadata: $o->metadata,
                    ),
                    $overrides,
                );
                $this->overrideRepository->createMany($newOverrides);
            }

            $mediaLinks = $this->contentMediaRepository->findByContentId($content->id);
            if ($mediaLinks !== []) {
                $mediaIds = array_map(fn ($link) => $link['media_id'], $mediaLinks);
                $this->contentMediaRepository->sync($newContent->id, $mediaIds);
            }
        }

        $this->eventDispatcher->dispatch(...$newCampaign->domainEvents);

        return CampaignOutput::fromEntity($newCampaign, [
            'total_contents' => count($contents),
            'draft' => count($contents),
            'ready' => 0,
            'scheduled' => 0,
            'published' => 0,
        ]);
    }
}
