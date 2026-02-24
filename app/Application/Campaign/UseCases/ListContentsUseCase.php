<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\ContentListOutput;
use App\Application\Campaign\DTOs\ContentOutput;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListContentsUseCase
{
    public function __construct(
        private readonly ContentRepositoryInterface $contentRepository,
    ) {}

    public function execute(string $campaignId): ContentListOutput
    {
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
