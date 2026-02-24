<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\ContentOutput;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Exceptions\ContentNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetContentUseCase
{
    public function __construct(
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly ContentNetworkOverrideRepositoryInterface $overrideRepository,
        private readonly ContentMediaRepositoryInterface $contentMediaRepository,
    ) {}

    public function execute(string $organizationId, string $contentId): ContentOutput
    {
        $content = $this->contentRepository->findById(Uuid::fromString($contentId));

        if ($content === null || (string) $content->organizationId !== $organizationId || $content->isDeleted()) {
            throw new ContentNotFoundException($contentId);
        }

        $overrides = $this->overrideRepository->findByContentId($content->id);
        $media = $this->contentMediaRepository->findByContentId($content->id);

        return ContentOutput::fromEntity($content, $overrides, $media);
    }
}
