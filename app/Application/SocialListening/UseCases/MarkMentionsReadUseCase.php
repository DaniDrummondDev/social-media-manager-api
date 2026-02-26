<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\MarkMentionsReadInput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;

final class MarkMentionsReadUseCase
{
    public function __construct(
        private readonly MentionRepositoryInterface $mentionRepository,
    ) {}

    public function execute(MarkMentionsReadInput $input): void
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $this->mentionRepository->markManyAsRead(
            organizationId: $organizationId,
            ids: $input->mentionIds,
        );
    }
}
