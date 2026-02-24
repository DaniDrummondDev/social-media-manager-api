<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\ContentOutput;
use App\Application\Campaign\DTOs\CreateContentInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Entities\ContentNetworkOverride;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

final class CreateContentUseCase
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly ContentNetworkOverrideRepositoryInterface $overrideRepository,
        private readonly ContentMediaRepositoryInterface $contentMediaRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateContentInput $input): ContentOutput
    {
        $campaign = $this->campaignRepository->findById(Uuid::fromString($input->campaignId));

        if ($campaign === null || (string) $campaign->organizationId !== $input->organizationId || $campaign->isDeleted()) {
            throw new CampaignNotFoundException($input->campaignId);
        }

        $content = Content::create(
            organizationId: Uuid::fromString($input->organizationId),
            campaignId: Uuid::fromString($input->campaignId),
            createdBy: Uuid::fromString($input->userId),
            title: $input->title,
            body: $input->body,
            hashtags: $input->hashtags,
        );

        $this->contentRepository->create($content);

        $overrides = [];
        if ($input->networkOverrides !== []) {
            $overrides = array_map(
                fn (array $data) => ContentNetworkOverride::create(
                    contentId: $content->id,
                    provider: SocialProvider::from($data['provider']),
                    title: $data['title'] ?? null,
                    body: $data['body'] ?? null,
                    hashtags: $data['hashtags'] ?? null,
                    metadata: $data['metadata'] ?? null,
                ),
                $input->networkOverrides,
            );
            $this->overrideRepository->createMany($overrides);
        }

        $media = [];
        if ($input->mediaIds !== []) {
            $this->contentMediaRepository->sync($content->id, $input->mediaIds);
            $media = $this->contentMediaRepository->findByContentId($content->id);
        }

        $this->eventDispatcher->dispatch(...$content->domainEvents);

        return ContentOutput::fromEntity($content, $overrides, $media);
    }
}
