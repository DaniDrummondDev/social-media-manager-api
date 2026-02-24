<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\ContentOutput;
use App\Application\Campaign\DTOs\UpdateContentInput;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\ContentNetworkOverride;
use App\Domain\Campaign\Exceptions\ContentNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

final class UpdateContentUseCase
{
    public function __construct(
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly ContentNetworkOverrideRepositoryInterface $overrideRepository,
        private readonly ContentMediaRepositoryInterface $contentMediaRepository,
    ) {}

    public function execute(UpdateContentInput $input): ContentOutput
    {
        $content = $this->contentRepository->findById(Uuid::fromString($input->contentId));

        if ($content === null || (string) $content->organizationId !== $input->organizationId || $content->isDeleted()) {
            throw new ContentNotFoundException($input->contentId);
        }

        $content = $content->update(
            title: $input->title,
            body: $input->body,
            hashtags: $input->hashtags,
        );

        $this->contentRepository->update($content);

        if ($input->networkOverrides !== null) {
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
            $this->overrideRepository->replaceForContent($content->id, $overrides);
        }

        if ($input->mediaIds !== null) {
            $this->contentMediaRepository->sync($content->id, $input->mediaIds);
        }

        $overrides = $this->overrideRepository->findByContentId($content->id);
        $media = $this->contentMediaRepository->findByContentId($content->id);

        return ContentOutput::fromEntity($content, $overrides, $media);
    }
}
