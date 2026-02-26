<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\GetMentionDetailsInput;
use App\Application\SocialListening\DTOs\MentionOutput;
use App\Application\SocialListening\Exceptions\MentionNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;

final class GetMentionDetailsUseCase
{
    public function __construct(
        private readonly MentionRepositoryInterface $mentionRepository,
    ) {}

    public function execute(GetMentionDetailsInput $input): MentionOutput
    {
        $mentionId = Uuid::fromString($input->mentionId);
        $organizationId = Uuid::fromString($input->organizationId);

        $mention = $this->mentionRepository->findById($mentionId);

        if ($mention === null || (string) $mention->organizationId !== (string) $organizationId) {
            throw new MentionNotFoundException();
        }

        return MentionOutput::fromEntity($mention);
    }
}
